<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssistantAnswer;
use App\Services\AssistantAI;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AssistantUsageController extends Controller
{
    public function index()
    {
        $tz     = config('shop.timezone');
        $today  = now($tz)->toDateString();
        $from7  = now($tz)->subDays(6)->toDateString();
        $from30 = now($tz)->subDays(29)->toDateString();

        $rows = DB::table('assistant_usage')->where('day', '>=', $from30)->get(['day', 'mode', 'total']);

        $labels = ['ai' => 'AI answers', 'simple' => 'Simple mode', 'saved' => 'Saved answers', 'limited' => 'Limit reached'];

        $byMode = collect($labels)->map(fn (string $label, string $mode) => [
            'label' => $label,
            'today' => (int) $rows->where('mode', $mode)->where('day', $today)->sum('total'),
            'week'  => (int) $rows->where('mode', $mode)->filter(fn ($r) => $r->day >= $from7)->sum('total'),
            'month' => (int) $rows->where('mode', $mode)->sum('total'),
        ]);

        // Questions that were answered (the limit counter is not an answer)
        $answered = fn ($set) => (int) $set->reject(fn ($r) => $r->mode === 'limited')->sum('total');
        $totals   = [
            'today' => $answered($rows->where('day', $today)),
            'week'  => $answered($rows->filter(fn ($r) => $r->day >= $from7)),
            'month' => $answered($rows),
        ];

        // One number for each of the last 14 days
        $series = collect();
        foreach (range(13, 0) as $ago) {
            $day = now($tz)->subDays($ago)->toDateString();
            $series->put($day, $answered($rows->where('day', $day)));
        }

        $top = AssistantAnswer::where('hits', '>', 0)->orderByDesc('hits')->take(5)->get(['id', 'question', 'hits']);

        $status = [
            'ai_on'      => AssistantAI::available(),
            'key_set'    => filled(config('assistant.api_key')),
            'enabled'    => (bool) config('assistant.enabled'),
            'model'      => (string) config('assistant.model'),
            'per_person' => (int) config('assistant.per_person_daily', 30),
            'per_ip'     => (int) config('assistant.per_ip_daily', 150),
            'per_site'   => (int) config('assistant.total_daily', 800),
            'site_today' => (int) Cache::get('assistant:' . now()->format('Ymd') . ':all', 0),
        ];

        return view('admin.assistant.usage', compact('byMode', 'totals', 'series', 'top', 'status'))->with('max', (int) $series->max());
    }
}