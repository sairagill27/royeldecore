<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            // If the product is deleted, its gallery rows go with it
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');                                      // where the photo is saved
            $table->string('alt', 160)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);      // 1, 2, 3... in upload order
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};