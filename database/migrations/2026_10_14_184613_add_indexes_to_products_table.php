<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Indexes make the shop and homepage queries faster as products grow
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['status', 'is_featured'], 'products_status_featured_index');
            $table->index(['category_id', 'status'], 'products_category_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_status_featured_index');
            $table->dropIndex('products_category_status_index');
        });
    }
};
