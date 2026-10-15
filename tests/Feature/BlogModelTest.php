<?php

namespace Tests\Feature;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BlogModelTest extends TestCase
{
    use RefreshDatabase;

    // Makes a post. Pass values to change any of them.
    private function makePost(array $overrides = []): BlogPost
    {
        return BlogPost::create(array_merge([
            'user_id'      => $this->makeAdmin()->id,
            'title'        => 'A test post',
            'slug'         => Str::lower(Str::random(10)),
            'content'      => 'Some content.',
            'status'       => 'published',
            'published_at' => now()->subDay(),
        ], $overrides));
    }

    public function test_only_published_posts_whose_date_has_arrived_are_public(): void
    {
        $live = $this->makePost(['title' => 'Live']);
        $this->makePost(['status' => 'draft']);                    // draft
        $this->makePost(['published_at' => now()->addDay()]);      // scheduled for tomorrow
        $this->makePost(['published_at' => null]);                 // no date

        $this->assertSame([$live->id], BlogPost::published()->pluck('id')->all());
    }

    public function test_a_post_knows_its_category_author_and_tags(): void
    {
        $category = BlogCategory::create(['name' => 'Bedroom', 'slug' => 'bedroom']);
        $post     = $this->makePost(['blog_category_id' => $category->id]);

        $post->tags()->attach([
            Tag::create(['name' => 'Cozy', 'slug' => 'cozy'])->id,
            Tag::create(['name' => 'Small', 'slug' => 'small'])->id,
        ]);

        $this->assertSame('Bedroom', $post->category->name);
        $this->assertNotNull($post->author);
        $this->assertCount(2, $post->tags);
        $this->assertSame(1, Tag::where('slug', 'cozy')->first()->posts()->count());
    }

    public function test_deleting_a_category_keeps_its_posts(): void
    {
        $category = BlogCategory::create(['name' => 'Kitchen', 'slug' => 'kitchen']);
        $post     = $this->makePost(['blog_category_id' => $category->id]);

        $category->delete();

        $this->assertNull($post->fresh()->blog_category_id);
        $this->assertDatabaseHas('blog_posts', ['id' => $post->id]);
    }

    public function test_reading_time_is_worked_out_from_the_word_count(): void
    {
        $this->assertSame(1, $this->makePost(['content' => 'Just a few words.'])->readingMinutes());
        $this->assertSame(3, $this->makePost(['content' => str_repeat('word ', 450)])->readingMinutes());
    }
}
