<?php

namespace Tests\Feature;

use App\Models\AssistantAnswer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AssistantExtrasTest extends TestCase
{
    use RefreshDatabase;

    // (The helper names must not clash with Laravel's own get(), post()... test methods.)
    private function saved(array $o = []): AssistantAnswer
    {
        return AssistantAnswer::create(array_merge([
            'question'  => 'How does cash on delivery work?',
            'keywords'  => 'cash on delivery, cod',
            'answer'    => 'You pay in cash when your order arrives.',
            'is_active' => true,
        ], $o));
    }

    private function ask(string $message)
    {
        return $this->postJson(route('assistant.ask'), ['message' => $message]);
    }

    private function form(array $o = []): array
    {
        return array_merge([
            'question'  => 'Do you sell lamps?',
            'keywords'  => 'lamp, lamps, lighting',
            'answer'    => 'Please open the shop and use the search box.',
            'is_active' => '1',
        ], $o);
    }

    // ---------- saved answers ----------

    public function test_a_saved_answer_replies_at_once_without_the_ai_and_counts_a_use(): void
    {
        Http::fake();
        config(['assistant.enabled' => true, 'assistant.api_key' => 'test-key']);
        $answer = $this->saved();

        $this->ask('Please tell me about Cash on Delivery!')->assertOk()
            ->assertJson(['mode' => 'saved', 'reply' => 'You pay in cash when your order arrives.'])
            ->assertJsonCount(0, 'products');

        Http::assertNothingSent();
        $this->assertSame(1, (int) $answer->fresh()->hits);
    }

    public function test_a_message_that_is_exactly_a_saved_question_always_matches(): void
    {
        $this->saved(['question' => 'How can I track my order?', 'keywords' => 'zzzunusedword']);

        $this->ask('how can i track my order')->assertOk()->assertJson(['mode' => 'saved']);
    }

    public function test_only_whole_words_match_and_the_longest_phrase_wins(): void
    {
        config(['assistant.api_key' => null]);
        $this->saved(['question' => 'About COD', 'keywords' => 'cod']);
        $this->saved(['question' => 'Delivery in general', 'keywords' => 'delivery', 'answer' => 'General delivery answer.']);
        $this->saved(['question' => 'Delivery charge', 'keywords' => 'delivery charge', 'answer' => 'The charge answer.']);

        $this->ask('how do I decode this?')->assertOk()->assertJson(['mode' => 'simple']);     // "cod" is not a whole word here
        $this->ask('What is the delivery charge?')->assertOk()->assertJson(['reply' => 'The charge answer.']);
        $this->ask('delivery please')->assertOk()->assertJson(['reply' => 'General delivery answer.']);
    }

    public function test_it_works_in_other_languages_too(): void
    {
        $this->saved(['question' => 'Kitne paise?', 'keywords' => 'kitne paise, qeemat', 'answer' => 'Qeemat har product par likhi hoti hai.']);

        $this->ask('Is ki Qeemat kya hai')->assertOk()->assertJson(['mode' => 'saved']);
    }

    public function test_a_hidden_answer_is_never_used(): void
    {
        config(['assistant.api_key' => null]);
        $this->saved(['is_active' => false]);

        $this->ask('cash on delivery')->assertOk()->assertJson(['mode' => 'simple']);
    }

    public function test_saved_answers_suggest_other_questions_as_follow_up_buttons(): void
    {
        $this->saved();
        $this->saved(['question' => 'How do I place an order?', 'keywords' => 'how to order']);
        $this->saved(['question' => 'What is the delivery charge?', 'keywords' => 'delivery charge']);

        $this->ask('cash on delivery')->assertOk()
            ->assertJsonCount(2, 'followups')
            ->assertJsonMissing(['followups' => ['How does cash on delivery work?']]);
    }

    public function test_saved_answers_still_work_after_the_daily_limit_is_reached(): void
    {
        Http::fake();
        config(['assistant.api_key' => null, 'assistant.per_person_daily' => 1]);
        $this->saved();
        $this->withSession(['assistant_vid' => str_repeat('a', 32)]);

        $this->ask('wall clock')->assertOk();
        $this->ask('vase')->assertStatus(429);
        $this->ask('cash on delivery')->assertOk()->assertJson(['mode' => 'saved']);
    }

    // ---------- usage counters ----------

    public function test_every_kind_of_answer_is_counted_without_saving_any_words(): void
    {
        Http::fake();
        config(['assistant.api_key' => null, 'assistant.per_person_daily' => 1]);
        $this->saved();
        $this->withSession(['assistant_vid' => str_repeat('a', 32)]);

        $this->ask('cash on delivery')->assertJson(['mode' => 'saved']);
        $this->ask('wall clock')->assertJson(['mode' => 'simple']);
        $this->ask('mirror')->assertStatus(429);

        foreach (['saved' => 1, 'simple' => 1, 'limited' => 1] as $mode => $total) {
            $this->assertSame($total, (int) DB::table('assistant_usage')->where('mode', $mode)->sum('total'), $mode);
        }

        $this->assertSame(['day', 'id', 'mode', 'total'], collect(DB::table('assistant_usage')->first())->keys()->sort()->values()->all());
    }

    // ---------- fairer limits ----------

    public function test_people_on_one_address_do_not_share_a_personal_limit_but_the_address_has_a_cap(): void
    {
        Http::fake();
        config(['assistant.api_key' => null, 'assistant.per_person_daily' => 1, 'assistant.per_ip_daily' => 3]);

        $this->withSession(['assistant_vid' => str_repeat('a', 32)])->ask('wall clock')->assertOk();
        $this->ask('mirror')->assertStatus(429);                                              // the same visitor again
        $this->withSession(['assistant_vid' => str_repeat('b', 32)])->ask('vase')->assertOk();            // another person, same address
        $this->withSession(['assistant_vid' => str_repeat('c', 32)])->ask('lamp')->assertOk();
        $this->withSession(['assistant_vid' => str_repeat('d', 32)])->ask('table')->assertStatus(429);    // the address cap is reached
    }

    public function test_a_private_id_is_made_once_and_kept_in_the_session(): void
    {
        Http::fake();
        config(['assistant.api_key' => null]);

        $this->ask('wall clock')->assertOk();
        $id = session('assistant_vid');
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', (string) $id);

        $this->ask('mirror')->assertOk();
        $this->assertSame($id, session('assistant_vid'));        // the same visit keeps the same id

        $this->flushSession();
        $this->ask('vase')->assertOk();
        $this->assertNotSame($id, session('assistant_vid'));     // a new visit gets a new id
    }

    // ---------- the admin pages ----------

    public function test_staff_can_add_and_edit_an_answer_and_any_html_is_removed(): void
    {
        $staff = $this->makeStaff();

        $this->actingAs($staff)->post(route('admin.assistant-answers.store'), $this->form([
            'answer' => "<b>Line one</b> <script>alert(1)</script>\n\nLine two",
        ]))->assertSessionHasNoErrors()->assertRedirect(route('admin.assistant-answers.index'));

        $answer = AssistantAnswer::firstOrFail();
        $this->assertStringNotContainsString('<', $answer->answer);
        $this->assertStringContainsString('Line two', $answer->answer);

        $this->actingAs($staff)->put(route('admin.assistant-answers.update', $answer), $this->form(['answer' => 'A new answer.', 'is_active' => '0']))
            ->assertSessionHasNoErrors();

        $this->assertSame('A new answer.', $answer->fresh()->answer);
        $this->assertFalse($answer->fresh()->is_active);
    }

    public function test_bad_details_are_refused(): void
    {
        $staff = $this->makeStaff();
        $this->saved(['question' => 'Do you sell lamps?']);

        foreach ([
            ['question' => ''],
            ['question' => str_repeat('a', 81)],
            ['question' => 'Do you sell lamps?'],       // already used
            ['keywords' => ''],
            ['keywords' => ' , , '],
            ['keywords' => 'a, b'],                    // too short to be useful
            ['keywords' => str_repeat('a', 401)],
            ['answer' => ''],
            ['answer' => str_repeat('a', 801)],
        ] as $bad) {
            $this->actingAs($staff)->post(route('admin.assistant-answers.store'), $this->form($bad))
                ->assertSessionHasErrors(array_key_first($bad));
        }

        $this->assertDatabaseCount('assistant_answers', 1);
    }

    public function test_only_an_admin_can_delete_and_customers_and_visitors_cannot_manage_answers(): void
    {
        $answer = $this->saved();

        $this->actingAs($this->makeStaff())->delete(route('admin.assistant-answers.destroy', $answer))->assertForbidden();
        $this->assertDatabaseHas('assistant_answers', ['id' => $answer->id]);

        $this->actingAs($this->makeAdmin())->delete(route('admin.assistant-answers.destroy', $answer))
            ->assertRedirect(route('admin.assistant-answers.index'));
        $this->assertDatabaseCount('assistant_answers', 0);

        $answer = $this->saved();
        $this->actingAs($this->makeCustomer());
        $this->get(route('admin.assistant-answers.index'))->assertForbidden();
        $this->get(route('admin.assistant-usage'))->assertForbidden();
        $this->post(route('admin.assistant-answers.store'), $this->form())->assertForbidden();
        $this->put(route('admin.assistant-answers.update', $answer), $this->form())->assertForbidden();

        auth()->logout();
        $this->get(route('admin.assistant-answers.index'))->assertRedirect(route('login'));
        $this->get(route('admin.assistant-usage'))->assertRedirect(route('login'));
        $this->post(route('admin.assistant-answers.store'), $this->form())->assertRedirect(route('login'));
    }

    public function test_the_list_shows_questions_status_and_uses_and_the_menu_links_to_it(): void
    {
        $this->saved()->forceFill(['hits' => 7])->save();
        $this->saved(['question' => 'Hidden one?', 'keywords' => 'hiddenword', 'is_active' => false]);

        $this->actingAs($this->makeStaff())->get(route('admin.assistant-answers.index'))->assertOk()
            ->assertSee('How does cash on delivery work?')
            ->assertSee('Active')->assertSee('Hidden')
            ->assertSee('7')
            ->assertSee('Add an answer')
            ->assertSee('Assistant answers')
            ->assertSee(route('admin.assistant-answers.index'), false);
    }

    public function test_the_usage_page_shows_the_counts_and_the_ai_status_but_never_the_key(): void
    {
        config(['assistant.enabled' => true, 'assistant.api_key' => 'secret-key-xyz']);
        $this->saved()->forceFill(['hits' => 4])->save();
        DB::table('assistant_usage')->insert([
            ['day' => now(config('shop.timezone'))->toDateString(), 'mode' => 'saved', 'total' => 4],
            ['day' => now(config('shop.timezone'))->toDateString(), 'mode' => 'ai', 'total' => 2],
        ]);

        $this->actingAs($this->makeStaff())->get(route('admin.assistant-usage'))->assertOk()
            ->assertSee('Assistant usage')
            ->assertSee('Saved answers')
            ->assertSee('The AI key is set')
            ->assertSee('How does cash on delivery work?')
            ->assertDontSee('secret-key-xyz')
            ->assertViewHas('totals', fn ($t) => $t['today'] === 6);

        config(['assistant.api_key' => null]);
        $this->actingAs($this->makeStaff())->get(route('admin.assistant-usage'))->assertOk()->assertSee('The AI key is not set');
    }

    // ---------- the chat page and the starter command ----------

    public function test_the_chat_page_offers_the_most_used_saved_questions_as_buttons(): void
    {
        $this->get('/assistant')->assertOk()->assertSee('Ideas for a small living room');

        $this->saved(['question' => 'What is your secret handshake?', 'keywords' => 'handshake']);

        $this->get('/assistant')->assertOk()
            ->assertSee('What is your secret handshake?')
            ->assertDontSee('Ideas for a small living room');
    }

    public function test_the_starter_command_adds_six_true_answers_once_and_never_overwrites(): void
    {
        config(['shop.currency' => 'Rs.', 'shop.shipping_fee' => 250, 'shop.free_shipping_over' => 15000, 'contact.whatsapp' => '+92 300-1234567', 'contact.email' => 'hello@example.com']);
        AssistantAnswer::create(['question' => 'How can I track my order?', 'keywords' => 'my own words', 'answer' => 'My own answer.', 'is_active' => true]);

        $this->artisan('assistant:seed-answers')->assertExitCode(0);
        $this->assertDatabaseCount('assistant_answers', 6);
        $this->assertSame('My own answer.', AssistantAnswer::where('question', 'How can I track my order?')->value('answer'));

        $this->artisan('assistant:seed-answers')->assertExitCode(0);
        $this->assertDatabaseCount('assistant_answers', 6);

        $this->assertStringContainsString('Rs. 250', AssistantAnswer::where('question', 'What is the delivery charge?')->value('answer'));
        $contact = AssistantAnswer::where('question', 'How can I contact the shop?')->value('answer');
        $this->assertStringContainsString('923001234567', $contact);
        $this->assertStringContainsString('hello@example.com', $contact);

        // Nothing is promised that the shop has not decided
        foreach (AssistantAnswer::all() as $item) {
            $this->assertDoesNotMatchRegularExpression('/\b(return|refund|exchange|warranty|guarantee|within \d+|business days|working days)\b/i', $item->answer);
        }

        $this->ask('what is the delivery fee')->assertOk()->assertJson(['mode' => 'saved']);
    }
}