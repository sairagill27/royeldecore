<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PolishTest extends TestCase
{
    use RefreshDatabase;

    private function hiddenCategory(): Category
    {
        return Category::create(['name' => 'Secret Room', 'slug' => 'secret-room', 'is_active' => false, 'sort_order' => 9]);
    }

    // ---------- hidden categories ----------

    public function test_a_hidden_category_page_shows_404(): void
    {
        $this->hiddenCategory();

        $this->get('/shop/secret-room')->assertNotFound();
    }

    public function test_products_in_a_hidden_category_are_hidden_everywhere(): void
    {
        $product = $this->makeProduct(['name' => 'Hidden Lamp', 'category_id' => $this->hiddenCategory()->id]);

        $this->get('/shop')->assertOk()->assertDontSee('Hidden Lamp');
        $this->get('/search?q=Hidden')->assertOk()->assertDontSee('Hidden Lamp');
        $this->get('/products/' . $product->slug)->assertNotFound();
        $this->post('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertNotFound();
    }

    public function test_a_product_without_a_category_is_still_visible(): void
    {
        $this->makeProduct(['name' => 'Loose Vase', 'category_id' => null]);

        $this->get('/shop')->assertSee('Loose Vase');
    }

    public function test_the_homepage_shows_real_visible_categories_only(): void
    {
        Category::create(['name' => 'Lamps', 'slug' => 'lamps', 'is_active' => true, 'sort_order' => 1]);
        $this->hiddenCategory();

        $this->get('/')->assertOk()->assertSee('Lamps')->assertDontSee('Secret Room');
    }

    // ---------- search ----------

    public function test_search_finds_active_products_by_name(): void
    {
        $this->makeProduct(['name' => 'Brass Vase']);
        $this->makeProduct(['name' => 'Draft Vase', 'status' => 'draft']);
        $this->makeProduct(['name' => 'Gold Clock']);

        $this->get('/search?q=Vase')->assertOk()
            ->assertSee('Brass Vase')
            ->assertDontSee('Draft Vase')
            ->assertDontSee('Gold Clock');
    }

    public function test_search_also_looks_in_the_category_name(): void
    {
        $this->makeProduct(['name' => 'Brass Vase']);   // inside the category "Test Decor"

        $this->get('/search?q=' . urlencode('Test Decor'))->assertOk()->assertSee('Brass Vase');
    }

    public function test_search_with_no_match_says_so(): void
    {
        $this->makeProduct();

        $this->get('/search?q=zzzzzz')->assertOk()->assertSee('No products found');
    }

    public function test_a_very_short_or_empty_search_asks_for_more(): void
    {
        $this->get('/search?q=a')->assertOk()->assertSee('at least 2 characters');
        $this->get('/search')->assertOk();
    }

    public function test_odd_search_input_never_breaks_the_page(): void
    {
        $this->get('/search?q[]=x')->assertOk();
        $this->get('/search?q=' . urlencode(str_repeat('a', 500)))->assertOk();
        $this->get('/search?q=' . urlencode('<script>alert(1)</script>'))->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    // ---------- newsletter ----------

    public function test_a_visitor_can_subscribe_and_double_sign_ups_are_ignored(): void
    {
        $this->post('/newsletter', ['email' => 'Fan@Example.com'])->assertSessionHas('success');
        $this->post('/newsletter', ['email' => 'fan@example.com'])->assertSessionHas('success');

        $this->assertDatabaseCount('newsletter_subscribers', 1);
        $this->assertDatabaseHas('newsletter_subscribers', ['email' => 'fan@example.com']);
    }

    public function test_a_bad_email_is_refused(): void
    {
        $this->post('/newsletter', ['email' => 'not-an-email'])->assertSessionHas('error');
        $this->post('/newsletter', [])->assertSessionHas('error');

        $this->assertDatabaseCount('newsletter_subscribers', 0);
    }

    public function test_the_spam_trap_saves_nothing(): void
    {
        $this->post('/newsletter', ['email' => 'bot@example.com', 'company' => 'Buy cheap pills'])
            ->assertSessionHas('success');   // the bot is told it worked

        $this->assertDatabaseCount('newsletter_subscribers', 0);
    }

    public function test_unsubscribing_and_coming_back_works(): void
    {
        $subscriber = NewsletterSubscriber::create(['email' => 'fan@example.com', 'token' => Str::random(64)]);

        $this->get('/newsletter/unsubscribe/' . $subscriber->token)->assertRedirect('/');
        $this->assertNotNull($subscriber->fresh()->unsubscribed_at);

        // Subscribing again switches it back on, with no second row
        $this->post('/newsletter', ['email' => 'fan@example.com']);
        $this->assertNull($subscriber->fresh()->unsubscribed_at);
        $this->assertDatabaseCount('newsletter_subscribers', 1);
    }

    public function test_a_wrong_unsubscribe_link_shows_404(): void
    {
        $this->get('/newsletter/unsubscribe/' . Str::random(64))->assertNotFound();
    }
}
