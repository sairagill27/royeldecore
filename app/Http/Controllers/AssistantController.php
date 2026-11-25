<?php

namespace App\Http\Controllers;

use App\Exceptions\AssistantUnavailable;
use App\Models\AssistantAnswer;
use App\Models\AssistantUsage;
use App\Models\Category;
use App\Services\AssistantAI;
use App\Services\AssistantCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class AssistantController extends Controller
{
    // /assistant: the chat page
    public function show()
    {
        return view('assistant.index', [
            'ai'              => AssistantAI::available(),
            'categories'      => Category::where('is_active', true)->orderBy('sort_order')->take(3)->pluck('name'),
            'commonQuestions' => AssistantAnswer::active()->orderByDesc('hits')->orderBy('id')->take(3)->pluck('question'),
            'max'             => (int) config('assistant.max_chars', 500),
        ]);
    }

    // POST /assistant/ask: one question in, one answer out (as JSON). Nothing is saved except daily counters.
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

        // 1. A saved answer is free and instant. It never uses the AI or the daily limits.
        $saved = AssistantAnswer::match($message);
        if ($saved) {
            AssistantAnswer::whereKey($saved->id)->increment('hits');
            AssistantUsage::hit('saved');

            return response()->json([
                'reply'     => $saved->answer,
                'products'  => [],
                'followups' => AssistantAnswer::suggest($saved->id),
                'mode'      => 'saved',
            ]);
        }

        // 2. Daily limits protect the free plan. A signed-in person is counted by account, anyone else by a private
        //    cookie, so people who share one internet address (for example on a phone network) do not eat each other's limit.
        //    The address has a bigger cap of its own, which stops someone who throws their cookie away.
        $day       = now()->format('Ymd');
        $who       = $request->user() ? 'u' . $request->user()->id : 'v' . $this->visitorId($request);
        $personKey = "assistant:{$day}:{$who}";
        $ipKey     = "assistant:{$day}:ip:" . $request->ip();
        $totalKey  = "assistant:{$day}:all";

        if ((int) Cache::get($personKey, 0) >= (int) config('assistant.per_person_daily', 30)
            || (int) Cache::get($ipKey, 0) >= (int) config('assistant.per_ip_daily', 150)) {
            AssistantUsage::hit('limited');

            return $this->fail("You have reached today's limit of questions. Please try the Décor Helper, or come back tomorrow.", 429);
        }

        $siteIsFull = (int) Cache::get($totalKey, 0) >= (int) config('assistant.total_daily', 800);
        $this->count($personKey);
        $this->count($ipKey);
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

        AssistantUsage::hit($mode);

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

    // A private random number kept in the visitor's session (the session cookie that Laravel already uses).
    // People who share one internet address do not eat each other's limit, and no extra cookie is needed.
    private function visitorId(Request $request): string
    {
        $id = $request->session()->get('assistant_vid');

        if (! is_string($id) || ! preg_match('/^[a-f0-9]{32}$/', $id)) {
            $id = bin2hex(random_bytes(16));
            $request->session()->put('assistant_vid', $id);
        }

        return $id;
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