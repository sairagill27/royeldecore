<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Answers that the shop writes itself
        if (! Schema::hasTable('assistant_answers')) {
            Schema::create('assistant_answers', function (Blueprint $table) {
                $table->id();
                $table->string('question', 80)->unique();          // also the text of the suggestion button
                $table->string('keywords', 400);                   // words or phrases, separated by commas
                $table->text('answer');                            // plain text
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedInteger('hits')->default(0);       // how many times it was used
                $table->timestamps();
            });
        }

        // One row per day and kind of answer: only counters, no personal data
        if (! Schema::hasTable('assistant_usage')) {
            Schema::create('assistant_usage', function (Blueprint $table) {
                $table->id();
                $table->date('day');
                $table->string('mode', 12);                        // ai, simple, saved or limited
                $table->unsignedInteger('total')->default(0);

                $table->unique(['day', 'mode']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_usage');
        Schema::dropIfExists('assistant_answers');
    }
};