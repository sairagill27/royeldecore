<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. The links
        if (! Schema::hasTable('affiliate_links')) {
            Schema::create('affiliate_links', function (Blueprint $table) {
                $table->id();
                $table->string('name', 120);
                $table->string('merchant', 60);                              // the store, for example Daraz
                $table->string('slug', 80)->unique();                        // used in /go/{slug} and [[affiliate:slug]]
                $table->string('url', 2000);                                 // the full affiliate link from the program
                $table->string('description', 200)->nullable();
                $table->string('section', 60)->nullable();                   // a heading on the Recommended page
                $table->string('image')->nullable();
                $table->string('status', 10)->default('active')->index();    // active, paused or expired
                $table->date('expires_at')->nullable();                      // after this day the link hides itself
                $table->boolean('is_featured')->default(false);
                $table->boolean('is_direct')->default(false);                // true = no redirect, straight to the store
                // Show this link under blog articles of this category
                $table->foreignId('blog_category_id')->nullable()->constrained('blog_categories')->nullOnDelete();
                $table->timestamp('last_checked_at')->nullable();            // when a person last opened the link to check it
                $table->unsignedBigInteger('clicks_total')->default(0);
                $table->timestamps();
            });
        } elseif (! Schema::hasColumn('affiliate_links', 'status')) {
            // An older version of the table: bring it up to date
            Schema::table('affiliate_links', function (Blueprint $table) {
                $table->string('status', 10)->default('active')->index();
                $table->date('expires_at')->nullable();
                $table->boolean('is_featured')->default(false);
                $table->foreignId('blog_category_id')->nullable()->constrained('blog_categories')->nullOnDelete();
                $table->timestamp('last_checked_at')->nullable();
            });

            if (Schema::hasColumn('affiliate_links', 'is_active')) {
                DB::table('affiliate_links')->where('is_active', false)->update(['status' => 'paused']);
                Schema::table('affiliate_links', function (Blueprint $table) {
                    $table->dropColumn('is_active');
                });
            }
        }

        // 2. One row per link per day: only a counter, no personal data
        if (! Schema::hasTable('affiliate_clicks')) {
            Schema::create('affiliate_clicks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('affiliate_link_id')->constrained('affiliate_links')->cascadeOnDelete();
                $table->date('day');
                $table->unsignedInteger('clicks')->default(0);

                $table->unique(['affiliate_link_id', 'day']);
            });
        }

        // 3. The activity log: who changed what
        if (! Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                // If the staff account is deleted, the entry stays (user_id becomes empty)
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('action', 60)->index();        // for example affiliate.created
                $table->string('subject', 120)->nullable();   // what it was about, for example the link's name
                $table->text('details')->nullable();          // never holds passwords, keys or affiliate addresses
                $table->string('ip', 45)->nullable();         // only for signed-in staff
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_clicks');
        Schema::dropIfExists('affiliate_links');
        Schema::dropIfExists('audit_logs');
    }
};