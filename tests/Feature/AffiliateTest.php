<?php

namespace Tests\Feature;

use App\Models\AffiliateLink;
use App\Models\AuditLog;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AffiliateTest extends TestCase
{
    use RefreshDatabase;

    private const HUMAN = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36';
    private const STORE = 'https://www.daraz.pk/products/cordless-drill-i123.html?aff=abc';

    private function makeLink(array $o = []): AffiliateLink
    {
        return AffiliateLink::create(array_merge([
            'name'     => 'Cordless Drill',
            'merchant' => 'Daraz',
            'slug'     => 'cordless-drill',
            'url'      => self::STORE,
            'status'   => 'active',
        ], $o));
    }

    // (The helper names must not clash with Laravel's own get(), post()... test methods.)
    private function makeArticle(string $content, array $o = []): BlogPost
    {
        return BlogPost::create(array_merge([
            'user_id'      => $this->makeAdmin()->id,
            'title'        => 'How to hang a mirror',
            'slug'         => 'hang-a-mirror',
            'content'      => $content,
            'status'       => 'published',
            'published_at' => now()->subDay(),
        ], $o));
    }

    private function form(array $o = []): array
    {
        return array_merge([
            'name'        => 'Cordless Drill',
            'merchant'    => 'Daraz',
            'url'         => self::STORE,
            'description' => 'A solid drill for hanging heavy mirrors.',
            'section'     => 'Decorative Lighting',
            'status'      => 'active',
        ], $o);
    }

    private function day(int $daysAgo = 0): string
    {
        return now(config('shop.timezone'))->subDays($daysAgo)->toDateString();
    }

    // A visit from one visitor (an address and a browser). The same visitor twice within 30 minutes counts once.
    private function visit(string $uri, string $agent = self::HUMAN, string $ip = '10.0.0.1')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])->withHeaders(['User-Agent' => $agent])->get($uri);
    }

    private function clicks(AffiliateLink $link): int
    {
        return (int) DB::table('affiliate_clicks')->where('affiliate_link_id', $link->id)->sum('clicks');
    }

    // ---------- which addresses are safe ----------

    public function test_only_full_https_addresses_of_other_stores_are_safe(): void
    {
        foreach ([self::STORE, 'https://daraz.pk/x', 'https://www.amazon.com/dp/B0ABC?tag=shop-20', 'https://s.click.aliexpress.com/e/_abc'] as $good) {
            $this->assertTrue(AffiliateLink::safeUrl($good), $good);
        }

        $bad = [
            null, '', 'daraz.pk/x', 'http://www.daraz.pk/x', 'javascript:alert(1)', 'data:text/html,<b>x</b>', 'ftp://daraz.pk/x',
            'https://', 'https://127.0.0.1/x', 'https://localhost/x', 'https://[::1]/x', 'https://shop.local/x',
            'https://user:pass@daraz.pk/x', 'https://daraz.pk/a b', "https://daraz.pk/\nx", 'https://exämple.com/x',
            'https://localhost', 'https://' . str_repeat('a', 2000) . '.com',
        ];
        foreach ($bad as $address) {
            $this->assertFalse(AffiliateLink::safeUrl($address), (string) $address);
        }
        $this->assertFalse(AffiliateLink::safeUrl(['https://daraz.pk']));
        $this->assertFalse(AffiliateLink::safeUrl(url('/shop')));   // never back to this website
    }

    // ---------- adding and editing ----------

    public function test_staff_can_add_a_link_and_the_short_name_is_made_for_them(): void
    {
        $staff = $this->makeStaff();

        $this->actingAs($staff)->post(route('admin.affiliate-links.store'), $this->form())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.affiliate-links.index'));

        $link = AffiliateLink::firstOrFail();
        $this->assertSame('cordless-drill', $link->slug);
        $this->assertSame('active', $link->status);
        $this->assertSame('Decorative Lighting', $link->section);
        $this->assertFalse($link->is_direct);
        $this->assertFalse($link->is_featured);
        $this->assertNull($link->expires_at);
        $this->assertNull($link->blog_category_id);
        $this->assertSame(0, (int) $link->clicks_total);

        $this->actingAs($staff)->post(route('admin.affiliate-links.store'), $this->form())->assertSessionHasNoErrors();
        $this->assertSame(['cordless-drill', 'cordless-drill-2'], AffiliateLink::orderBy('id')->pluck('slug')->all());
    }

    public function test_a_chosen_short_name_must_be_clean_and_unused(): void
    {
        $staff = $this->makeStaff();

        $this->actingAs($staff)->post(route('admin.affiliate-links.store'), $this->form(['slug' => 'my-drill']))->assertSessionHasNoErrors();
        $this->actingAs($staff)->post(route('admin.affiliate-links.store'), $this->form(['slug' => 'my-drill']))->assertSessionHasErrors('slug');
        $this->actingAs($staff)->post(route('admin.affiliate-links.store'), $this->form(['slug' => 'Bad Name!']))->assertSessionHasErrors('slug');

        $this->assertDatabaseCount('affiliate_links', 1);
    }

    public function test_a_photo_is_saved_under_a_random_safe_name(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('The PHP "gd" extension is off.');
        }
        Storage::fake('public');

        $this->actingAs($this->makeStaff())
            ->post(route('admin.affiliate-links.store'), $this->form(['image' => UploadedFile::fake()->image('my drill.jpg', 600, 400)]))
            ->assertSessionHasNoErrors();

        $link = AffiliateLink::firstOrFail();
        $this->assertStringStartsWith('affiliate/', $link->image);
        $this->assertStringNotContainsString('my drill', $link->image);
        $this->assertContains(pathinfo($link->image, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'webp']);   // never .php
        Storage::disk('public')->assertExists($link->image);
    }

    public function test_a_disguised_or_oversized_photo_is_refused(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('The PHP "gd" extension is off.');
        }
        Storage::fake('public');
        $staff = $this->makeStaff();

        foreach ([
            UploadedFile::fake()->image('evil.php', 100, 100),                       // a real picture with a script name
            UploadedFile::fake()->image('evil.php.jpg', 100, 100)->size(3000),       // too big
            UploadedFile::fake()->create('shell.php', 10, 'application/x-php'),
            UploadedFile::fake()->create('logo.svg', 5, 'image/svg+xml'),
            UploadedFile::fake()->create('note.txt', 5, 'text/plain'),
        ] as $file) {
            $this->actingAs($staff)->post(route('admin.affiliate-links.store'), $this->form(['image' => $file]))->assertSessionHasErrors('image');
        }

        $this->assertDatabaseCount('affiliate_links', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_bad_details_are_refused(): void
    {
        $staff = $this->makeStaff();

        $cases = [
            ['url' => 'http://www.daraz.pk/x'],
            ['url' => 'javascript:alert(1)'],
            ['url' => 'daraz.pk/x'],
            ['url' => 'https://127.0.0.1/x'],
            ['url' => 'https://user:pass@daraz.pk/x'],
            ['url' => ''],
            ['name' => ''],
            ['merchant' => ''],
            ['description' => str_repeat('a', 201)],
            ['section' => 'Sofas and Beds'],
            ['status' => 'hacked'],
            ['status' => ''],
            ['expires_at' => 'tomorrow'],
            ['blog_category_id' => 9999],
        ];

        foreach ($cases as $bad) {
            $this->actingAs($staff)->post(route('admin.affiliate-links.store'), $this->form($bad))
                ->assertSessionHasErrors(array_key_first($bad));
        }

        $this->assertDatabaseCount('affiliate_links', 0);
    }

    public function test_sections_come_from_the_list_but_an_old_value_can_stay(): void
    {
        $staff = $this->makeStaff();
        $link  = $this->makeLink(['section' => 'Old Free Text']);

        // The old value may stay
        $this->actingAs($staff)->put(route('admin.affiliate-links.update', $link), $this->form(['section' => 'Old Free Text']))
            ->assertSessionHasNoErrors();

        // Another value that is not in the list may not be chosen
        $this->actingAs($staff)->put(route('admin.affiliate-links.update', $link), $this->form(['section' => 'Something Else']))
            ->assertSessionHasErrors('section');

        // A section from the list is fine
        $this->actingAs($staff)->put(route('admin.affiliate-links.update', $link), $this->form(['section' => 'Designer Wall Clocks']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Designer Wall Clocks', $link->fresh()->section);

        $this->actingAs($staff)->get(route('admin.affiliate-links.create'))->assertOk()
            ->assertSee('Wooden &amp; MDF Home Decor', false)
            ->assertSee('Wall Panels &amp; Decorative Accessories', false);
    }

    public function test_a_link_can_be_edited_and_only_an_admin_can_delete(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('affiliate/old.jpg', 'x');
        $link  = $this->makeLink(['image' => 'affiliate/old.jpg']);
        $staff = $this->makeStaff();

        // An empty short name keeps the old one, so shortcodes already written keep working
        $this->actingAs($staff)->put(route('admin.affiliate-links.update', $link), $this->form([
            'name' => 'Renamed Drill', 'slug' => '', 'status' => 'paused', 'is_direct' => '1', 'is_featured' => '1', 'expires_at' => '2099-12-31',
        ]))->assertSessionHasNoErrors();

        $link->refresh();
        $this->assertSame('Renamed Drill', $link->name);
        $this->assertSame('cordless-drill', $link->slug);
        $this->assertSame('paused', $link->status);
        $this->assertTrue($link->is_direct);
        $this->assertTrue($link->is_featured);
        $this->assertSame('2099-12-31', $link->expires_at->toDateString());

        $this->actingAs($staff)->delete(route('admin.affiliate-links.destroy', $link))->assertForbidden();
        $this->assertDatabaseHas('affiliate_links', ['id' => $link->id]);

        $this->actingAs($this->makeAdmin())->delete(route('admin.affiliate-links.destroy', $link))
            ->assertRedirect(route('admin.affiliate-links.index'));
        $this->assertDatabaseMissing('affiliate_links', ['id' => $link->id]);
        Storage::disk('public')->assertMissing('affiliate/old.jpg');
    }

    public function test_customers_and_visitors_cannot_manage_links(): void
    {
        $link = $this->makeLink();

        $this->actingAs($this->makeCustomer());
        $this->get(route('admin.affiliate-links.index'))->assertForbidden();
        $this->get(route('admin.affiliate-analytics'))->assertForbidden();
        $this->get(route('admin.activity'))->assertForbidden();
        $this->post(route('admin.affiliate-links.store'), $this->form())->assertForbidden();
        $this->put(route('admin.affiliate-links.update', $link), $this->form(['name' => 'Hacked']))->assertForbidden();
        $this->patch(route('admin.affiliate-links.toggle', $link))->assertForbidden();
        $this->patch(route('admin.affiliate-links.checked', $link))->assertForbidden();
        $this->delete(route('admin.affiliate-links.destroy', $link))->assertForbidden();

        auth()->logout();
        $this->get(route('admin.affiliate-links.index'))->assertRedirect(route('login'));
        $this->get(route('admin.affiliate-analytics'))->assertRedirect(route('login'));
        $this->get(route('admin.activity'))->assertRedirect(route('login'));
        $this->post(route('admin.affiliate-links.store'), $this->form())->assertRedirect(route('login'));
        $this->patch(route('admin.affiliate-links.toggle', $link))->assertRedirect(route('login'));

        $this->assertSame('Cordless Drill', $link->fresh()->name);
        $this->assertSame('active', $link->fresh()->status);
    }

    // ---------- the admin list ----------

    public function test_the_admin_list_shows_status_shortcode_clicks_and_can_be_searched_and_filtered(): void
    {
        $this->makeLink(['section' => 'Decorative Lighting']);
        $this->makeLink(['slug' => 'paused-lamp', 'name' => 'Paused Lamp', 'status' => 'paused']);
        $this->makeLink(['slug' => 'expired-rug', 'name' => 'Expired Rug', 'status' => 'expired', 'merchant' => 'AliExpress']);
        $this->makeLink(['slug' => 'late-fan', 'name' => 'Late Fan', 'expires_at' => $this->day(1)]);   // active, but its end date passed
        $staff = $this->makeStaff();

        $this->actingAs($staff)->get(route('admin.affiliate-links.index'))->assertOk()
            ->assertSee('[[affiliate:cordless-drill]]', false)
            ->assertSee('Active')->assertSee('Paused')->assertSee('Expired')
            ->assertViewHas('counts', fn ($c) => $c === ['all' => 4, 'active' => 1, 'paused' => 1, 'expired' => 2]);

        $this->actingAs($staff)->get(route('admin.affiliate-links.index', ['state' => 'paused']))->assertOk()
            ->assertSee('Paused Lamp')->assertDontSee('Cordless Drill')->assertDontSee('Expired Rug');

        $this->actingAs($staff)->get(route('admin.affiliate-links.index', ['state' => 'expired']))->assertOk()
            ->assertSee('Expired Rug')->assertSee('Late Fan')->assertDontSee('Cordless Drill');

        $this->actingAs($staff)->get(route('admin.affiliate-links.index', ['q' => 'aliexpress']))->assertOk()
            ->assertSee('Expired Rug')->assertDontSee('Paused Lamp');

        $this->actingAs($staff)->get(route('admin.affiliate-links.index', ['section' => 'Decorative Lighting']))->assertOk()
            ->assertSee('Cordless Drill')->assertDontSee('Paused Lamp');

        // Odd values never break the page
        $this->actingAs($staff)->get('/admin/affiliate-links?q[]=x&state=hack&section[]=y')->assertOk();
        $this->actingAs($staff)->get(route('admin.affiliate-links.index', ['q' => '%']))->assertOk()->assertDontSee('Cordless Drill');
    }

    // ---------- pausing, end dates and checking ----------

    public function test_pausing_and_resuming(): void
    {
        $link  = $this->makeLink();
        $staff = $this->makeStaff();

        $this->actingAs($staff)->patch(route('admin.affiliate-links.toggle', $link))->assertSessionHas('success');
        $this->assertSame('paused', $link->fresh()->status);
        $this->visit('/go/cordless-drill')->assertNotFound();

        $this->actingAs($staff)->patch(route('admin.affiliate-links.toggle', $link))->assertSessionHas('success');
        $this->assertSame('active', $link->fresh()->status);
        $this->visit('/go/cordless-drill')->assertStatus(302);

        // A link whose end date has passed cannot be resumed until the date is changed
        $late = $this->makeLink(['slug' => 'late-one', 'status' => 'paused', 'expires_at' => $this->day(1)]);
        $this->actingAs($staff)->patch(route('admin.affiliate-links.toggle', $late))->assertSessionHas('error');
        $this->assertSame('paused', $late->fresh()->status);
    }

    public function test_an_end_date_hides_the_link_after_that_day(): void
    {
        $this->makeLink(['expires_at' => $this->day(1)]);                                                  // ended yesterday
        $this->makeLink(['slug' => 'ends-today', 'name' => 'Ends Today', 'expires_at' => $this->day(0)]);  // still works today

        $this->visit('/go/cordless-drill')->assertNotFound();
        $this->visit('/go/ends-today')->assertStatus(302);

        $this->get('/recommended')->assertOk()->assertSee('Ends Today')->assertDontSee('Cordless Drill');

        $this->actingAs($this->makeStaff())->get(route('admin.affiliate-links.index'))->assertOk()->assertSee('Expired');
    }

    public function test_marking_a_link_as_checked(): void
    {
        $link  = $this->makeLink();
        $staff = $this->makeStaff();

        $this->actingAs($staff)->get(route('admin.affiliate-links.index'))->assertOk()->assertSee('Check link');

        $this->actingAs($staff)->patch(route('admin.affiliate-links.checked', $link))->assertSessionHas('success');
        $this->assertNotNull($link->fresh()->last_checked_at);
        $this->actingAs($staff)->get(route('admin.affiliate-links.index'))->assertOk()->assertDontSee('Check link');

        // After 30 days it needs a check again
        $link->forceFill(['last_checked_at' => now()->subDays(40)])->save();
        $this->actingAs($staff)->get(route('admin.affiliate-links.index'))->assertOk()->assertSee('Check link');

        // A paused link is not nagged about
        $link->forceFill(['status' => 'paused'])->save();
        $this->actingAs($staff)->get(route('admin.affiliate-links.index'))->assertOk()->assertDontSee('Check link');
    }

    // ---------- the short address ----------

    public function test_the_short_address_sends_visitors_to_the_store_and_keeps_search_engines_out(): void
    {
        $this->makeLink();

        $response = $this->visit('/go/cordless-drill')->assertStatus(302)->assertRedirect(self::STORE);

        $this->assertSame('noindex, nofollow', $response->headers->get('X-Robots-Tag'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_people_are_counted_once_per_visit_and_robots_are_not_counted(): void
    {
        $link = $this->makeLink();

        $this->visit('/go/cordless-drill', self::HUMAN, '10.0.0.2')->assertStatus(302);
        $this->visit('/go/cordless-drill', self::HUMAN, '10.0.0.2')->assertStatus(302);   // the same visitor again: not counted again
        $this->assertSame(1, $this->clicks($link));

        $this->visit('/go/cordless-drill', self::HUMAN, '10.0.0.3')->assertStatus(302);   // another visitor
        $this->assertSame(2, $this->clicks($link));

        foreach (['Googlebot/2.1 (+http://www.google.com/bot.html)', 'WhatsApp/2.23.20', 'curl/8.4.0', 'facebookexternalhit/1.1', ''] as $n => $agent) {
            $this->visit('/go/cordless-drill', $agent, '10.0.1.' . $n)->assertStatus(302);   // still sent on, just not counted
        }
        $this->assertSame(2, $this->clicks($link));

        // After 30 minutes the first visitor counts again
        $this->travel(31)->minutes();
        $this->visit('/go/cordless-drill', self::HUMAN, '10.0.0.2')->assertStatus(302);
        $this->assertSame(3, $this->clicks($link));
        $this->assertSame(3, (int) $link->fresh()->clicks_total);
    }

    public function test_hidden_paused_expired_unknown_and_unsafe_links_show_404(): void
    {
        $this->makeLink(['slug' => 'paused-one', 'status' => 'paused']);
        $this->makeLink(['slug' => 'expired-one', 'status' => 'expired']);
        $this->makeLink(['slug' => 'late-one', 'expires_at' => $this->day(1)]);
        $unsafe = $this->makeLink(['slug' => 'unsafe-one']);
        DB::table('affiliate_links')->where('id', $unsafe->id)->update(['url' => 'http://insecure.example/x']);

        foreach (['paused-one', 'expired-one', 'late-one', 'does-not-exist', 'unsafe-one', 'Paused-One'] as $slug) {
            $this->visit('/go/' . $slug)->assertNotFound();
        }

        $this->assertSame(0, (int) DB::table('affiliate_clicks')->sum('clicks'));
    }

    public function test_a_blocked_address_is_logged_once_without_stopping_anything(): void
    {
        $unsafe = $this->makeLink(['slug' => 'unsafe-one', 'name' => 'Unsafe One']);
        DB::table('affiliate_links')->where('id', $unsafe->id)->update(['url' => 'http://insecure.example/x']);

        $this->visit('/go/unsafe-one')->assertNotFound();
        $this->visit('/go/unsafe-one', self::HUMAN, '10.0.0.9')->assertNotFound();

        $entries = AuditLog::where('action', 'affiliate.blocked')->get();
        $this->assertCount(1, $entries);
        $this->assertSame('Unsafe One', $entries[0]->subject);
        $this->assertNull($entries[0]->user_id);
        $this->assertNull($entries[0]->ip);                                   // visitors are not recorded with an address
        $this->assertStringNotContainsString('insecure.example', (string) $entries[0]->details);
    }

    public function test_a_direct_link_skips_the_redirect(): void
    {
        $this->makeLink(['is_direct' => true]);
        $this->makeLink(['slug' => 'other-one', 'name' => 'Other Thing', 'is_direct' => false]);

        $this->get('/recommended')->assertOk()
            ->assertSee('href="' . self::STORE . '"', false)
            ->assertSee('href="' . route('affiliate.go', 'other-one') . '"', false)
            ->assertDontSee('href="' . route('affiliate.go', 'cordless-drill') . '"', false);
    }

    public function test_a_problem_with_the_click_counter_never_stops_the_visitor(): void
    {
        $this->makeLink();
        Schema::drop('affiliate_clicks');   // makes the counter fail

        $this->visit('/go/cordless-drill')->assertStatus(302)->assertRedirect(self::STORE);
    }

    // ---------- the shortcode in an article ----------

    public function test_a_shortcode_becomes_a_card_with_a_notice_and_the_right_link_attributes(): void
    {
        $this->makeLink(['description' => 'A solid drill.']);
        $this->makeArticle("Intro words.\n\n[[affiliate:cordless-drill]]\n\nOutro words.");

        $html = $this->get('/blog/hang-a-mirror')->assertOk()
            ->assertSee('Affiliate link')
            ->assertSee('Cordless Drill')
            ->assertSee('View on Daraz')
            ->assertSee('A solid drill.')
            ->assertSee('This page contains affiliate links')
            ->assertSee('href="' . route('legal.affiliate') . '"', false)
            ->assertSee('href="' . route('affiliate.go', 'cordless-drill') . '"', false)
            ->assertSee('rel="sponsored nofollow noopener"', false)
            ->assertSee('target="_blank"', false)
            ->assertSee('Intro words.')
            ->assertSee('Outro words.')
            ->assertDontSee('[[affiliate', false)
            ->assertDontSee('SGHAFFILIATESLOT')
            ->getContent();

        $this->assertLessThan(strpos($html, 'Cordless Drill'), strpos($html, 'This page contains affiliate links'));   // the notice comes first
    }

    public function test_an_unknown_paused_or_expired_shortcode_leaves_nothing_behind(): void
    {
        $this->makeLink(['slug' => 'paused-one', 'status' => 'paused']);
        $this->makeLink(['slug' => 'late-one', 'expires_at' => $this->day(1)]);
        $unsafe = $this->makeLink(['slug' => 'unsafe-one']);
        DB::table('affiliate_links')->where('id', $unsafe->id)->update(['url' => 'http://insecure.example/x']);

        $this->makeArticle("Words.\n\n[[affiliate:nothing-here]]\n\n[[affiliate:paused-one]]\n\n[[affiliate:late-one]]\n\n[[affiliate:unsafe-one]]\n\nMore words.");

        $this->get('/blog/hang-a-mirror')->assertOk()
            ->assertSee('Words.')
            ->assertSee('More words.')
            ->assertDontSee('Affiliate link')
            ->assertDontSee('This page contains affiliate links')
            ->assertDontSee('[[affiliate', false)
            ->assertDontSee('SGHAFFILIATESLOT');
    }

    public function test_an_inline_shortcode_is_removed_and_an_article_shows_at_most_eight_cards(): void
    {
        $this->makeLink();
        $this->makeArticle('See [[affiliate:cordless-drill]] for this.');

        $this->get('/blog/hang-a-mirror')->assertOk()
            ->assertSee('See')
            ->assertDontSee('Cordless Drill')
            ->assertDontSee('[[affiliate', false)
            ->assertDontSee('SGHAFFILIATESLOT');

        BlogPost::query()->delete();
        $this->makeArticle(implode("\n\n", array_fill(0, 10, '[[affiliate:cordless-drill]]')));

        $html = $this->get('/blog/hang-a-mirror')->assertOk()->getContent();
        $this->assertSame(8, substr_count($html, 'card sgh-affiliate '));
    }

    public function test_summaries_hide_the_shortcode_and_the_editor_preview_shows_the_card(): void
    {
        $this->makeLink();
        $post = $this->makeArticle("[[affiliate:cordless-drill]]\n\nIntro words here.", ['excerpt' => null]);

        $this->assertStringContainsString('Intro words here.', $post->summary());
        $this->assertStringNotContainsString('[[', $post->summary());

        $html = $this->actingAs($this->makeStaff())
            ->postJson(route('admin.posts.preview'), ['content' => "Hello.\n\n[[affiliate:cordless-drill]]"])
            ->assertOk()
            ->json('html');

        $this->assertStringContainsString('Affiliate link', $html);
        $this->assertStringContainsString('This page contains affiliate links', $html);
    }

    // ---------- related picks under an article ----------

    public function test_related_picks_appear_under_articles_of_the_chosen_category(): void
    {
        $lighting = BlogCategory::create(['name' => 'Lighting', 'slug' => 'lighting', 'sort_order' => 1]);
        $curtains = BlogCategory::create(['name' => 'Curtains', 'slug' => 'curtains', 'sort_order' => 2]);

        $this->makeLink(['slug' => 'l-one', 'name' => 'Pick One', 'blog_category_id' => $lighting->id, 'is_featured' => true]);
        $this->makeLink(['slug' => 'l-two', 'name' => 'Pick Two', 'blog_category_id' => $lighting->id]);
        $this->makeLink(['slug' => 'l-three', 'name' => 'Pick Three', 'blog_category_id' => $lighting->id]);
        $this->makeLink(['slug' => 'l-four', 'name' => 'Pick Four', 'blog_category_id' => $lighting->id]);
        $this->makeLink(['slug' => 'l-paused', 'name' => 'Pick Paused', 'blog_category_id' => $lighting->id, 'status' => 'paused']);
        $this->makeLink(['slug' => 'c-one', 'name' => 'Curtain Pick', 'blog_category_id' => $curtains->id]);

        $this->makeArticle('Plain words.', ['blog_category_id' => $lighting->id]);

        // Up to 3, the featured one first, then the newest. Paused links and other categories stay out.
        $html = $this->get('/blog/hang-a-mirror')->assertOk()
            ->assertSee('Related picks')
            ->assertSee('This page contains affiliate links')
            ->assertSeeInOrder(['Pick One', 'Pick Four', 'Pick Three'])
            ->assertDontSee('Pick Two')
            ->assertDontSee('Pick Paused')
            ->assertDontSee('Curtain Pick')
            ->getContent();
        $this->assertSame(3, substr_count($html, 'card sgh-affiliate '));

        // A link already placed inside the article is not repeated below
        BlogPost::query()->delete();
        $this->makeArticle("Words.\n\n[[affiliate:l-one]]", ['blog_category_id' => $lighting->id]);

        $html = $this->get('/blog/hang-a-mirror')->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, 'Pick One'));
        $this->assertSame(4, substr_count($html, 'card sgh-affiliate '));   // one inside, three below

        // An article without a category shows nothing
        BlogPost::query()->delete();
        $this->makeArticle('Words.', ['blog_category_id' => null]);
        $this->get('/blog/hang-a-mirror')->assertOk()->assertDontSee('Related picks');
    }

    // ---------- the Recommended page ----------

    public function test_the_recommended_page_groups_active_links_and_shows_the_notice(): void
    {
        $this->makeLink(['section' => 'Designer Wall Clocks']);
        $this->makeLink(['slug' => 'warm-bulb', 'name' => 'Warm Bulb', 'section' => 'Decorative Lighting']);
        $this->makeLink(['slug' => 'plain-one', 'name' => 'Plain Thing', 'section' => null]);
        $this->makeLink(['slug' => 'paused-one', 'name' => 'Secret Thing', 'status' => 'paused']);

        $this->get('/recommended')->assertOk()
            ->assertSee('class="sgh-tool-head"', false)
            ->assertSee('Recommended for your home')
            ->assertSee('This page contains affiliate links')
            ->assertSeeInOrder(['Decorative Lighting', 'Warm Bulb', 'Designer Wall Clocks', 'Cordless Drill', 'More picks', 'Plain Thing'])
            ->assertSee('rel="sponsored nofollow noopener"', false)
            ->assertSee('"@type":"BreadcrumbList"', false)
            ->assertSee('<link rel="canonical" href="' . route('recommended') . '">', false)
            ->assertDontSee('Secret Thing')
            ->assertDontSee('noindex', false);
    }

    public function test_featured_picks_come_first_and_only_without_a_search(): void
    {
        $this->makeLink(['name' => 'Star Drill', 'is_featured' => true, 'section' => 'Decorative Lighting']);
        $this->makeLink(['slug' => 'warm-bulb', 'name' => 'Warm Bulb', 'section' => 'Decorative Lighting']);

        $html = $this->get('/recommended')->assertOk()
            ->assertSee('Featured picks')
            ->assertSeeInOrder(['Featured picks', 'Star Drill', 'Decorative Lighting', 'Warm Bulb'])
            ->getContent();
        $this->assertSame(1, substr_count($html, 'Star Drill'));   // not repeated in its section
        $this->assertStringContainsString('Featured', $html);

        $this->get('/recommended?q=drill')->assertOk()->assertDontSee('Featured picks')->assertSee('Star Drill');
    }

    public function test_the_recommended_page_can_be_searched_and_filtered(): void
    {
        $this->makeLink(['section' => 'Wooden & MDF Home Decor']);
        $this->makeLink(['slug' => 'warm-bulb', 'name' => 'Warm Bulb', 'merchant' => 'AliExpress', 'section' => 'Decorative Lighting']);

        $this->get('/recommended?q=drill')->assertOk()
            ->assertSee('Cordless Drill')->assertDontSee('Warm Bulb')
            ->assertSee('1 pick found')->assertSee('noindex', false);

        $this->get('/recommended?q=aliexpress')->assertOk()->assertSee('Warm Bulb')->assertDontSee('Cordless Drill');

        $this->get('/recommended?section=' . urlencode('Decorative Lighting'))->assertOk()
            ->assertSee('Warm Bulb')->assertDontSee('Cordless Drill')->assertSee('noindex', false);

        $this->get('/recommended?q=nothingmatches')->assertOk()->assertSee('No picks match your search');

        // A section that does not exist, a one-letter search, and odd values are ignored
        $this->get('/recommended?section=Nope')->assertOk()->assertSee('Cordless Drill')->assertSee('Warm Bulb');
        $this->get('/recommended?q=%25')->assertOk()->assertSee('Cordless Drill')->assertSee('Warm Bulb');
        $this->get('/recommended?q[]=x&section[]=y')->assertOk()->assertSee('Cordless Drill');

        // Wildcards typed by a visitor are treated as plain letters, so two of them match nothing
        $this->get('/recommended?q=%25%25')->assertOk()->assertSee('No picks match your search');

        // What was typed is never run as code
        $this->get('/recommended?q=' . urlencode('<script>alert(1)</script>'))->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_an_empty_recommended_page_is_friendly_and_kept_out_of_search_engines(): void
    {
        $this->get('/recommended')->assertOk()
            ->assertSee('No recommendations yet')
            ->assertDontSee('This page contains affiliate links')
            ->assertSee('noindex', false);

        // Only paused links: still empty for visitors
        $this->makeLink(['status' => 'paused']);
        $this->get('/recommended')->assertOk()->assertSee('No recommendations yet');
    }

    public function test_cards_have_accessible_link_text_and_photo_descriptions(): void
    {
        $this->makeLink(['image' => 'affiliate/x.jpg']);

        $this->get('/recommended')->assertOk()
            ->assertSee('(affiliate link, opens in a new tab)')
            ->assertSee('rel="sponsored nofollow noopener"', false)
            ->assertSee('alt="Cordless Drill"', false)
            ->assertSee('Check the current price and details on Daraz.');
    }

    public function test_names_are_never_run_as_code(): void
    {
        $this->makeLink(['name' => '<script>alert(1)</script>', 'merchant' => '<b>Shop</b>', 'description' => '<i>note</i>']);
        $this->makeArticle('[[affiliate:cordless-drill]]');

        foreach (['/recommended', '/blog/hang-a-mirror'] as $page) {
            $this->get($page)->assertOk()
                ->assertDontSee('<script>alert(1)</script>', false)
                ->assertDontSee('<b>Shop</b>', false)
                ->assertDontSee('<i>note</i>', false)
                ->assertSee('&lt;script&gt;', false);
        }

        $staff = $this->makeStaff();
        foreach ([route('admin.affiliate-links.index'), route('admin.affiliate-analytics')] as $page) {
            $this->actingAs($staff)->get($page)->assertOk()->assertDontSee('<script>alert(1)</script>', false);
        }
    }

    // ---------- footer, sitemap and robots ----------

    public function test_the_footer_link_and_the_sitemap_entry_appear_only_when_there_is_an_active_link(): void
    {
        $this->get('/')->assertOk()->assertDontSee('href="' . route('recommended') . '"', false);
        $this->get('/sitemap.xml')->assertOk()->assertDontSee(route('recommended'), false);

        $link = $this->makeLink();
        Cache::forget('affiliate.has');
        Cache::forget('seo.sitemap');

        $this->get('/')->assertOk()->assertSee('href="' . route('recommended') . '"', false)->assertSee('Recommended');
        $this->get('/sitemap.xml')->assertOk()->assertSee(route('recommended'), false);

        // Pausing the only link removes both straight away (the admin page clears the remembered answers)
        $this->actingAs($this->makeStaff())->patch(route('admin.affiliate-links.toggle', $link));
        auth()->logout();
        $this->get('/')->assertOk()->assertDontSee('href="' . route('recommended') . '"', false);
        $this->get('/sitemap.xml')->assertOk()->assertDontSee(route('recommended'), false);
    }

    public function test_the_live_robots_file_keeps_search_engines_out_of_the_short_addresses(): void
    {
        $this->app['env'] = 'production';

        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /go/');
    }

    // ---------- analytics ----------

    public function test_the_analytics_page_counts_clicks_by_day_link_and_section(): void
    {
        $drill = $this->makeLink(['section' => 'Decorative Lighting']);
        $bulb  = $this->makeLink(['slug' => 'warm-bulb', 'name' => 'Warm Bulb', 'section' => null]);

        $click = fn (AffiliateLink $link, int $ago, int $n) => DB::table('affiliate_clicks')->insert(
            ['affiliate_link_id' => $link->id, 'day' => $this->day($ago), 'clicks' => $n]
        );
        $click($drill, 0, 3);    // today
        $click($drill, 3, 2);    // this week
        $click($drill, 10, 4);   // this month
        $click($drill, 40, 9);   // older: only in the all-time number
        $click($bulb, 0, 1);

        $this->actingAs($this->makeStaff())->get(route('admin.affiliate-analytics'))->assertOk()
            ->assertSee('Affiliate analytics')
            ->assertSee('Clicks are not sales')
            ->assertViewHas('totals', fn ($t) => $t === ['today' => 4, 'week' => 6, 'month' => 10, 'all' => 19])
            ->assertViewHas('series', fn ($s) => $s->count() === 30 && $s->sum() === 10)
            ->assertViewHas('top', fn ($rows) => $rows->count() === 2
                && $rows->first()->name === 'Cordless Drill' && (int) $rows->first()->clicks30 === 9 && (int) $rows->first()->clicks7 === 5)
            ->assertViewHas('bySection', fn ($rows) => $rows->count() === 2
                && $rows->first()->section_name === 'Decorative Lighting' && (int) $rows->first()->clicks30 === 9
                && $rows->last()->section_name === 'No section');
    }

    public function test_the_analytics_page_is_friendly_with_no_data_and_lists_links_to_check(): void
    {
        $this->makeLink();
        $fresh = $this->makeLink(['slug' => 'fresh-one', 'name' => 'Fresh One']);
        $fresh->forceFill(['last_checked_at' => now()])->save();   // not fillable from a form, so it is set directly

        $this->actingAs($this->makeStaff())->get(route('admin.affiliate-analytics'))->assertOk()
            ->assertSee('No clicks yet')
            ->assertSee('Links to check')
            ->assertSee('Cordless Drill')
            ->assertViewHas('needsCheck', fn ($links) => $links->count() === 1 && $links->first()->name === 'Cordless Drill')
            ->assertViewHas('totals', fn ($t) => $t === ['today' => 0, 'week' => 0, 'month' => 0, 'all' => 0]);
    }

    // ---------- the activity log ----------

    public function test_admin_changes_are_written_to_the_activity_log_without_addresses(): void
    {
        $staff = $this->makeStaff();

        $this->actingAs($staff)->post(route('admin.affiliate-links.store'), $this->form())->assertSessionHasNoErrors();
        $link = AffiliateLink::firstOrFail();

        $this->actingAs($staff)->put(route('admin.affiliate-links.update', $link), $this->form(['url' => 'https://www.daraz.pk/other-address']))->assertSessionHasNoErrors();
        $this->actingAs($staff)->patch(route('admin.affiliate-links.toggle', $link));
        $this->actingAs($staff)->patch(route('admin.affiliate-links.toggle', $link));
        $this->actingAs($staff)->patch(route('admin.affiliate-links.checked', $link));
        $this->actingAs($this->makeAdmin())->delete(route('admin.affiliate-links.destroy', $link));

        $this->assertSame(
            ['affiliate.created', 'affiliate.updated', 'affiliate.paused', 'affiliate.resumed', 'affiliate.checked', 'affiliate.deleted'],
            AuditLog::orderBy('id')->pluck('action')->all()
        );

        $created = AuditLog::where('action', 'affiliate.created')->first();
        $this->assertSame($staff->id, $created->user_id);
        $this->assertSame('Cordless Drill', $created->subject);
        $this->assertStringContainsString('cordless-drill', $created->details);
        $this->assertSame('127.0.0.1', $created->ip);

        $updated = AuditLog::where('action', 'affiliate.updated')->first();
        $this->assertStringContainsString('url', $updated->details);

        foreach (AuditLog::all() as $entry) {
            $this->assertStringNotContainsString('daraz.pk', (string) $entry->details);   // addresses are never written in the log
        }
    }

    public function test_the_activity_log_is_for_admins_only(): void
    {
        $this->actingAs($this->makeStaff())->post(route('admin.affiliate-links.store'), $this->form());

        $this->actingAs($this->makeStaff())->get(route('admin.activity'))->assertForbidden();

        $this->actingAs($this->makeAdmin())->get(route('admin.activity'))->assertOk()
            ->assertSee('Who changed what')
            ->assertSee('Link added')
            ->assertSee('Cordless Drill');

        $this->actingAs($this->makeAdmin())->get(route('admin.affiliate-links.index'))->assertOk()->assertSee('Activity log');
        $this->actingAs($this->makeStaff())->get(route('admin.affiliate-links.index'))->assertOk()->assertDontSee('Activity log');
    }
}