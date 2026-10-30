<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();

            // If the product or the customer is deleted, their reviews go with them
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->unsignedTinyInteger('rating');                       // 1 to 5
            $table->string('title', 100)->nullable();
            $table->text('body');
            $table->string('status', 20)->default('pending')->index();   // pending, approved or rejected

            $table->timestamps();

            // One review per product per customer
            $table->unique(['user_id', 'product_id']);
            $table->index(['product_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};