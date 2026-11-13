<?php

namespace App\Console\Commands;

use App\Exceptions\AssistantUnavailable;
use App\Services\AssistantAI;
use App\Services\AssistantCatalog;
use Illuminate\Console\Command;

class AssistantCheck extends Command
{
    protected $signature = 'assistant:test {question? : A question to ask}';

    protected $description = 'Sends one question to the AI service and shows what happened (to check your setup)';

    public function handle(): int
    {
        $this->line('Enabled: ' . (config('assistant.enabled') ? 'yes' : 'no'));
        $this->line('Model:   ' . config('assistant.model'));
        $this->line('API key: ' . (filled(config('assistant.api_key')) ? 'set' : 'NOT set'));

        if (! AssistantAI::available()) {
            $this->error('The assistant is in simple mode. Type your key after GEMINI_API_KEY= in .env, run php artisan config:clear, and try again.');

            return self::FAILURE;
        }

        $question = (string) ($this->argument('question') ?: 'Which wall clock would suit a small living room?');
        $catalog  = AssistantCatalog::context($question);
        $this->line('Products sent to the AI: ' . $catalog->count());

        try {
            $answer = AssistantAI::ask($question, [], $catalog);
        } catch (AssistantUnavailable $e) {
            $this->error('Failed (' . $e->reason . '): ' . $e->getMessage());
            $this->line('A "not found" error usually means the model name in .env is wrong. Copy a current free Flash model name from Google AI Studio.');

            return self::FAILURE;
        }

        $this->info('It works. The answer:');
        $this->line($answer['reply']);
        $this->line('Products named: ' . (implode(', ', $answer['slugs']) ?: 'none'));

        return self::SUCCESS;
    }
}