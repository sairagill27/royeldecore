<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AssistantTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'test-secret-key-123';

    private function aiOn(): void
    {
        config(['assistant.enabled' => true, 'assistant.api_key' => self::KEY, 'assistant.model' => 'test-model']);
    }

    private function aiOff(): void
    {
        config(['assistant.api_key' => null]);
    }

    private function piece(array $o = [])
    {
        return $this->makeProduct(array_merge(['stock' => 5, 'description' => '', 'image' => 'products/x.jpg'], $o));
    }

    // What Google sends back, with the JSON the AI was asked to write
    private function googleSays($content, int $status = 200)
    {
        $text = is_array($content) ? json_encode($content) : $content;

        return Http::response(['candidates' => [['content' => ['parts' => [['text' => $text]]]]]], $status);
    }

    // What Google sends back, as plain data
    private function googleBody($content): array
    {
        $text = is_array($content) ? json_encode($content) : $content;

        return ['candidates' => [['content' => ['parts' => [['text' => $text]]]]]];
    }
    private function fakeGoogle($response): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => $response]);
    }

    private function ask(string $message, array $extra = [])
    {
        return $this->postJson(route('assistant.ask'), array_merge(['message' => $message], $extra));
    }

    // ---------- the page ----------

    public function test_the_page_opens_in_simple_mode_without_a_key(): void
    {
        $this->aiOff();

        $this->get('/assistant')->assertOk()
            ->assertSee('class="sgh-tool-head"', false)
            ->assertSee('Décor Assistant')
            ->assertSee('Simple mode')
            ->assertSee('id="chatForm"', false)
            ->assertSee('data-ask-url="' . route('assistant.ask') . '"', false)
            ->assertSee('assets/js/assistant.js', false)
            ->assertSee('Talk to a person')
            ->assertSee('We do not save your chat')
            ->assertDontSee('Google');
    }

    public function test_the_page_says_so_when_an_ai_is_on_and_never_shows_the_key(): void
    {
        $this->aiOn();

        $this->get('/assistant')->assertOk()
            ->assertSee('AI assistant')
            ->assertSee('AI answers can be wrong')
            ->assertSee('Google')
            ->assertDontSee('Simple mode')
            ->assertDontSee(self::KEY);
    }

    public function test_the_whatsapp_button_needs_a_valid_number(): void
    {
        config(['contact.whatsapp' => null]);
        $this->get('/assistant')->assertOk()->assertDontSee('WhatsApp us')->assertSee('Contact form');

        config(['contact.whatsapp' => '+92 300-1234567']);
        $this->get('/assistant')->assertOk()->assertSee('WhatsApp us')->assertSee('https://wa.me/923001234567?text=', false);
    }

    public function test_starter_chips_use_the_category_names(): void
    {
        \App\Models\Category::create(['name' => 'Wall Clocks', 'slug' => 'wall-clocks', 'is_active' => true, 'sort_order' => 1]);

        $this->get('/assistant')->assertOk()->assertSee('Show me Wall Clocks');
    }

    // ---------- checking the question ----------

    public function test_empty_long_and_odd_questions_are_refused_and_nothing_is_sent(): void
    {
        Http::fake();
        $this->aiOn();

        $this->ask('')->assertStatus(422)->assertJsonStructure(['error']);
        $this->ask(str_repeat('a', 501))->assertStatus(422);
        $this->postJson(route('assistant.ask'), ['message' => ['x']])->assertStatus(422);
        $this->postJson(route('assistant.ask'), [])->assertStatus(422);

        Http::assertNothingSent();
    }

    // ---------- simple mode ----------

    public function test_simple_mode_matches_the_words_to_real_products_without_calling_anything(): void
    {
        Http::fake();
        $this->aiOff();
        $this->piece(['name' => 'Gold Wall Clock']);
        $this->piece(['name' => 'Plain Vase']);
        $this->piece(['name' => 'Draft Wall Clock', 'status' => 'draft']);

        $this->ask('I want a wall clock')->assertOk()
            ->assertJson(['mode' => 'simple'])
            ->assertJsonCount(1, 'products')
            ->assertJsonPath('products.0.name', 'Gold Wall Clock');

        Http::assertNothingSent();
    }

    public function test_simple_mode_is_friendly_when_nothing_matches(): void
    {
        $this->aiOff();

        $this->ask('xylophone')->assertOk()
            ->assertJson(['mode' => 'simple'])
            ->assertJsonCount(0, 'products')
            ->assertJsonPath('reply', fn ($reply) => str_contains($reply, 'could not find a piece'));
    }

    public function test_the_product_card_comes_from_the_database(): void
    {
        config(['shop.currency' => 'Rs.']);
        $this->aiOff();
        $piece = $this->piece(['name' => 'Brass Lamp', 'price' => 3500, 'slug' => 'brass-lamp']);

        $this->ask('lamp')->assertOk()
            ->assertJsonPath('products.0.price', 'Rs. 3,500')
            ->assertJsonPath('products.0.url', route('products.show', 'brass-lamp'))
            ->assertJsonPath('products.0.image', asset('storage/products/x.jpg'));
    }

    // ---------- AI mode ----------

    public function test_the_ai_answer_shows_only_products_that_really_exist(): void
    {
        config(['shop.currency' => 'Rs.']);
        $this->aiOn();
        $this->piece(['name' => 'Gold Wall Clock', 'price' => 3500, 'slug' => 'gold-wall-clock']);
        $this->piece(['name' => 'Hidden Clock', 'status' => 'draft', 'slug' => 'hidden-clock']);

        $this->fakeGoogle($this->googleSays([
            'reply'     => 'A wall clock would suit your living room.',
            'products'  => ['gold-wall-clock', 'invented-product', 'hidden-clock', 'gold-wall-clock'],
            'followups' => ['What colour is your wall?', 'Your budget?', 'Which room?', 'One too many'],
        ]));

        $this->ask('Which wall clock suits my living room?')->assertOk()
            ->assertJson(['mode' => 'ai', 'reply' => 'A wall clock would suit your living room.'])
            ->assertJsonCount(1, 'products')                          // invented, hidden and repeated ones are dropped
            ->assertJsonPath('products.0.name', 'Gold Wall Clock')
            ->assertJsonPath('products.0.price', 'Rs. 3,500')
            ->assertJsonCount(3, 'followups');
    }

    public function test_the_request_to_google_has_the_key_in_a_header_the_rules_and_only_real_products(): void
    {
        config(['shop.currency' => 'Rs.', 'shop.shipping_fee' => 250, 'shop.free_shipping_over' => 15000]);
        $this->aiOn();
        $this->piece(['name' => 'Gold Wall Clock']);
        $this->piece(['name' => 'Draft Mirror', 'status' => 'draft']);
        $this->piece(['name' => 'Sold Out Vase', 'stock' => 0]);
        $this->fakeGoogle($this->googleSays(['reply' => 'Hello']));

        $this->ask('wall clock')->assertOk();

        Http::assertSent(function (HttpRequest $request) {
            $system = $request->data()['system_instruction']['parts'][0]['text'] ?? '';

            return str_contains($request->url(), '/models/test-model:generateContent')
                && ! str_contains($request->url(), self::KEY)               // the key is never in the address
                && $request->hasHeader('x-goog-api-key', self::KEY)
                && ($request->data()['generationConfig']['responseMimeType'] ?? '') === 'application/json'
                && str_contains($system, 'Gold Wall Clock')
                && ! str_contains($system, 'Draft Mirror')
                && ! str_contains($system, 'Sold Out Vase')
                && str_contains($system, 'cash on delivery only')
                && str_contains($system, 'Rs. 250')
                && str_contains($system, 'never guess');
        });
    }

    public function test_earlier_messages_are_cleaned_and_alternate_starting_with_the_customer(): void
    {
        $this->aiOn();
        $this->fakeGoogle($this->googleSays(['reply' => 'ok']));

        $this->ask('Which clock?', ['history' => [
            ['role' => 'assistant', 'text' => 'Hello! (a greeting first)'],
            ['role' => 'user', 'text' => 'I like gold'],
            ['role' => 'user', 'text' => 'and a small room'],
            ['role' => 'bogus', 'text' => 'ignored'],
            ['role' => 'assistant', 'text' => '<b>Nice</b> choice'],
            'junk',
            ['role' => 'user', 'text' => ['not text']],
        ]])->assertOk();

        Http::assertSent(function (HttpRequest $request) {
            $contents = $request->data()['contents'];

            return array_column($contents, 'role') === ['user', 'model', 'user']
                && str_contains($contents[0]['parts'][0]['text'], 'I like gold')
                && str_contains($contents[0]['parts'][0]['text'], 'and a small room')
                && $contents[1]['parts'][0]['text'] === 'Nice choice'
                && $contents[2]['parts'][0]['text'] === 'Which clock?';
        });
    }

    public function test_plain_text_from_the_ai_is_used_as_the_answer(): void
    {
        $this->aiOn();
        $this->fakeGoogle($this->googleSays('Just some plain words, no JSON.'));

        $this->ask('hello')->assertOk()
            ->assertJson(['mode' => 'ai', 'reply' => 'Just some plain words, no JSON.'])
            ->assertJsonCount(0, 'products');
    }

    public function test_json_wrapped_in_a_code_block_is_understood(): void
    {
        $this->aiOn();
        $this->fakeGoogle($this->googleSays("```json\n{\"reply\": \"Fenced answer\", \"products\": [], \"followups\": []}\n```"));

        $this->ask('hello')->assertOk()->assertJson(['reply' => 'Fenced answer']);
    }

    public function test_html_is_removed_and_long_answers_are_cut(): void
    {
        $this->aiOn();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push($this->googleBody(['reply' => '<b>Bold</b> <script>alert(1)</script>words']))
            ->push($this->googleBody(['reply' => str_repeat('a', 5000)]))]);

        $this->ask('hello')->assertOk()
            ->assertJsonPath('reply', fn ($r) => ! str_contains($r, '<b>') && ! str_contains($r, '<script>'));

        $this->ask('hello again')->assertOk()
            ->assertJsonPath('reply', fn ($r) => mb_strlen($r) > 1000 && mb_strlen($r) <= 1203);
    }

    // ---------- when the AI cannot answer ----------

    public function test_every_kind_of_failure_falls_back_to_simple_mode(): void
    {
        $this->aiOn();
        $this->piece(['name' => 'Gold Wall Clock']);

        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push(['error' => ['message' => 'Quota exceeded']], 429)
            ->push(['error' => ['message' => 'Not found']], 404)
            ->push('boom', 500)
            ->push([], 200)
            ->push(['promptFeedback' => ['blockReason' => 'OTHER']], 200)]);

        foreach (range(1, 5) as $n) {
            $this->ask('wall clock ' . $n)->assertOk()->assertJson(['mode' => 'simple']);
        }

        Http::assertSentCount(5);   // each question really reached the (fake) service
    }

    public function test_a_connection_problem_falls_back_to_simple_mode(): void
    {
        $this->aiOn();
        Http::fake(function () {
            throw new ConnectionException('timeout');
        });

        $this->ask('wall clock')->assertOk()->assertJson(['mode' => 'simple']);
    }

    public function test_a_blocked_question_gets_a_polite_refusal(): void
    {
        $this->aiOn();
        $this->piece(['name' => 'Gold Wall Clock']);
        $this->fakeGoogle(Http::response(['promptFeedback' => ['blockReason' => 'SAFETY']], 200));

        $this->ask('wall clock')->assertOk()
            ->assertJson(['mode' => 'simple'])
            ->assertJsonCount(0, 'products')
            ->assertJsonPath('reply', fn ($r) => str_contains($r, 'I cannot help with that'));
    }

    public function test_a_wrong_model_name_in_the_settings_never_reaches_google(): void
    {
        Http::fake();
        $this->aiOn();
        config(['assistant.model' => 'bad model/../x']);

        $this->ask('wall clock')->assertOk()->assertJson(['mode' => 'simple']);

        Http::assertNothingSent();
    }

    // ---------- limits ----------

    public function test_one_person_gets_a_daily_number_of_questions(): void
    {
        Http::fake();
        $this->aiOff();
        config(['assistant.per_person_daily' => 2]);

        $this->ask('wall clock')->assertOk();
        $this->ask('mirror')->assertOk();
        $this->ask('vase')->assertStatus(429)->assertJsonStructure(['error']);
    }

    public function test_when_the_whole_site_reaches_its_daily_cap_the_ai_is_skipped(): void
    {
        $this->aiOn();
        config(['assistant.total_daily' => 1]);
        $this->fakeGoogle($this->googleSays(['reply' => 'AI answer']));

        $this->ask('first question')->assertOk()->assertJson(['mode' => 'ai']);
        $this->ask('second question')->assertOk()->assertJson(['mode' => 'simple']);

        Http::assertSentCount(1);
    }

    public function test_only_fifteen_questions_a_minute_are_allowed(): void
    {
        Http::fake();
        $this->aiOff();

        foreach (range(1, 15) as $n) {
            $this->ask('question number ' . $n)->assertOk();
        }

        $this->ask('one too many')->assertStatus(429);
    }

    // ---------- safety ----------

    public function test_the_key_never_appears_in_an_answer(): void
    {
        $this->aiOn();
        $this->fakeGoogle($this->googleSays(['reply' => 'Hello']));

        $this->assertStringNotContainsString(self::KEY, $this->ask('hello')->getContent());
    }

    public function test_what_a_visitor_types_is_never_sent_back_as_html(): void
    {
        $this->aiOff();

        $response = $this->ask('<script>alert(1)</script> wall clock')->assertOk();

        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
        $this->assertStringNotContainsString('<script>', $response->getContent());
    }

    // ---------- where it is linked from ----------

    public function test_the_floating_button_shows_on_shop_pages_but_not_on_the_chat_cart_or_checkout(): void
    {
        $this->get('/')->assertOk()->assertSee('sgh-chat-fab', false)->assertSee('Ask us');
        $this->get('/shop')->assertOk()->assertSee('sgh-chat-fab', false);

        $this->get('/assistant')->assertOk()->assertDontSee('sgh-chat-fab', false);
        $this->get('/cart')->assertOk()->assertDontSee('sgh-chat-fab', false);
    }

    public function test_the_assistant_is_in_the_menu_the_sitemap_the_helper_and_the_home_page(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('href="' . url('/assistant') . '"', false)
            ->assertSee('Ask the assistant');

        $this->get('/helper')->assertOk()->assertSee('Prefer to type your question?');
        $this->get('/sitemap.xml')->assertOk()->assertSee(route('assistant'), false);
    }

    // ---------- the check command ----------

    public function test_the_check_command_tells_you_when_the_key_is_missing_and_when_it_works(): void
    {
        $this->aiOff();
        $this->artisan('assistant:test')->assertExitCode(1);

        $this->aiOn();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push($this->googleBody(['reply' => 'It works.']))
            ->push(['error' => ['message' => 'model not found']], 404)]);

        $this->artisan('assistant:test')->assertExitCode(0);
        $this->artisan('assistant:test')->assertExitCode(1);
    }
}