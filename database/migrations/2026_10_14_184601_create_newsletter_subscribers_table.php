<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();                   // no duplicates
            $table->string('token', 64)->unique();               // secret part of the unsubscribe link
            $table->timestamp('unsubscribed_at')->nullable();    // empty = still subscribed
            $table->timestamps();                                // created_at = when they subscribed
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
    }
};
