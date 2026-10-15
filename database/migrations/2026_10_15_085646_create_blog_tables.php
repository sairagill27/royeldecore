<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Blog categories: Living Room, Bedroom, Kitchen...
        Schema::create('blog_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();            // web address part, no duplicates
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Tags: small keywords added to posts
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();
            $table->string('slug', 80)->unique();
            $table->timestamps();
        });

        // The posts
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();

            // If the author's account is deleted, the post stays (user_id becomes empty)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // If a category is deleted, the post stays without a category
            $table->foreignId('blog_category_id')->nullable()->constrained('blog_categories')->nullOnDelete();

            $table->string('title', 200);
            $table->string('slug', 220)->unique();
            $table->text('excerpt')->nullable();               // short text for blog cards
            $table->longText('content');                       // the article, written in Markdown

            $table->string('featured_image')->nullable();
            $table->string('featured_image_alt')->nullable();  // description for screen readers and SEO

            $table->string('status', 20)->default('draft');    // draft or published
            $table->timestamp('published_at')->nullable();     // a future date = scheduled

            $table->string('seo_title')->nullable();
            $table->string('meta_description', 320)->nullable();

            $table->timestamps();

            // Speeds up "all published posts, newest first"
            $table->index(['status', 'published_at']);
        });

        // Links posts and tags (many-to-many)
        Schema::create('post_tag', function (Blueprint $table) {
            $table->foreignId('blog_post_id')->constrained('blog_posts')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->primary(['blog_post_id', 'tag_id']);        // the same tag cannot be added twice
        });
    }

    public function down(): void 
    {
        Schema::dropIfExists('post_tag');
        Schema::dropIfExists('blog_posts');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('blog_categories');
    }
};
