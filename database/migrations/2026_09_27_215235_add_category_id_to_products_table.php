<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. category_id column: add it only if it is missing
        if (! Schema::hasColumn('products', 'category_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedBigInteger('category_id')->nullable()->after('id');
            });
        }

        // 2. Link category_id to categories.id (foreign key): add it only if missing.
        //    restrictOnDelete = a category with products cannot be deleted.
        $hasForeignKey = collect(Schema::getForeignKeys('products'))
            ->contains(fn($fk) => $fk['columns'] === ['category_id']);

        if (! $hasForeignKey) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreign('category_id')->references('id')->on('categories')->restrictOnDelete();
            });
        }

        // 3. The other columns: each one is added only if missing
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'slug')) {
                $table->string('slug')->nullable()->unique()->after('name');   // URL name
            }
            if (! Schema::hasColumn('products', 'sku')) {
                $table->string('sku')->nullable()->unique();                   // stock code
            }
            if (! Schema::hasColumn('products', 'status')) {
                $table->string('status')->default('draft');                    // draft or active
            }
            if (! Schema::hasColumn('products', 'is_featured')) {
                $table->boolean('is_featured')->default(false);                // homepage featured
            }
            if (! Schema::hasColumn('products', 'seo_title')) {
                $table->string('seo_title')->nullable();
            }
            if (! Schema::hasColumn('products', 'meta_description')) {
                $table->string('meta_description', 320)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn([
                'category_id',
                'slug',
                'sku',
                'status',
                'is_featured',
                'seo_title',
                'meta_description',
            ]);
        });
    }
};
