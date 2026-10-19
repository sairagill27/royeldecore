<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            // The word or phrase the article is about (used by the SEO score in Part B)
            $table->string('focus_keyword', 80)->nullable()->after('meta_description');
            // true = ask search engines NOT to list this article
            $table->boolean('noindex')->default(false)->after('focus_keyword');
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn(['focus_keyword', 'noindex']);
        });
    }
};
