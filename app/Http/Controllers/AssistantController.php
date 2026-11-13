<?php

namespace App\Http\Controllers;

use App\Exceptions\AssistantUnavailable;
use App\Models\Category;
use App\Services\AssistantAI;
use App\Services\AssistantCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AssistantController extends Controller
{
    // /assistant: the chat page
    public function show()
    {
        return view('assistant.index', [
            'ai'         => AssistantAI::available(),
            'categories' => Category::where('is_active', true)->orderBy('sort_order')->take(3)->pluck('name'),
            'max'        => (int) config('assistant.max_chars', 500),
        ]);
    }

    // POST /assistant/ask: one question in, one answer out (as JSON). Nothing is saved except two daily counters.
    public function ask(Request $request)
    {
        $max = (int) config('assistant.max_chars', 500);

        // SECURITY: only plain text is accepted (a trick like message[]=x counts as empty)
        $raw     = $request->input('message');
        $message = is_string($raw) ? Str::squish(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $raw)) : '';

        if ($message === '') {
            return $this->fail('Please type a question.', 422);
        }
        if (mb_strlen($message) > $max) {
            return $this->fail('Please keep your question under ' . $max . ' characters.', 422);
        }

        // Daily limits protect the free plan. Each person is counted by login, or by internet address.
        $day       = now()->format('Ymd');
        $who       = $request->user() ? 'u' . $request->user()->id : 'ip' . $request->ip();
        $personKey = "assistant:{$day}:{$who}";
        $totalKey  = "assistant:{$day}:all";

        if ((int) Cache::get($personKey, 0) >= (int) config('assistant.per_person_daily', 30)) {
            return $this->fail("You have reached today's limit of questions. Please try the Décor Helper, or come back tomorrow.", 429);
        }

        $siteIsFull = (int) Cache::get($totalKey, 0) >= (int) config('assistant.total_daily', 800);
        $this->count($personKey);
        $this->count($totalKey);

        $history = $this->cleanHistory($request->input('history'));
        $catalog = AssistantCatalog::context($message);
        $mode    = 'ai';

        try {
            if ($siteIsFull) {
                throw new AssistantUnavailable('busy');
            }

            $answer = AssistantAI::ask($message, $history, $catalog);
            if (trim($answer['reply']) === '') {
                throw new AssistantUnavailable('error', 'The AI sent an empty answer.');
            }

            // Only products that were in the catalog we sent can be shown. An invented one is dropped.
            $products  = collect($answer['slugs'])->map(fn ($slug) => $catalog->firstWhere('slug', $slug))->filter()->values();
            $reply     = $answer['reply'];
            $followups = $answer['followups'];
        } catch (AssistantUnavailable $e) {
            $mode = 'simple';

            if ($e->reason === 'blocked') {
                $products  = collect();
                $reply     = 'Sorry, I cannot help with that. I can help you choose home décor from our shop.';
                $followups = [];
            } else {
                $simple    = AssistantCatalog::simple($message);
                $products  = $simple['products'];
                $reply     = $simple['reply'];
                $followups = $simple['followups'];
            }
        }

        return response()->json([
            'reply'     => $reply,
            'products'  => $products->map(fn ($product) => AssistantCatalog::card($product))->all(),
            'followups' => $followups,
            'mode'      => $mode,
        ]);
    }

    // ---------- helpers ----------

    private function fail(string $message, int $status)
    {
        return response()->json(['error' => $message], $status);
    }

    // A counter that disappears at the end of the day
    private function count(string $key): void
    {
        Cache::add($key, 0, now()->endOfDay());
        Cache::increment($key);
    }

    // The earlier messages, checked one by one: only "user" and "assistant", plain text, short, at most 12
    private function cleanHistory(mixed $input): array
    {
        if (! is_array($input)) {
            return [];
        }

        $clean = [];

        foreach (array_slice($input, -1 * (int) config('assistant.max_turns', 6) * 2) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $role = $item['role'] ?? null;
            $text = $item['text'] ?? null;

            if (! in_array($role, ['user', 'assistant'], true) || ! is_string($text)) {
                continue;
            }

            $text = Str::limit(Str::squish(strip_tags($text)), 600, '');
            if ($text !== '') {
                $clean[] = ['role' => $role, 'text' => $text];
            }
        }

        return $clean;
    }
}