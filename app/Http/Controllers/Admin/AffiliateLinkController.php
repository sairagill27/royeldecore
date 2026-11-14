<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AffiliateLinkRequest;
use App\Models\AffiliateLink;
use App\Models\BlogCategory;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AffiliateLinkController extends Controller
{
    // ---------- LIST (with search and filters) ----------
    public function index(Request $request)
    {
        $text = function (string $key) use ($request): string {
            $value = $request->query($key);

            return is_string($value) ? Str::limit(Str::squish($value), 80, '') : '';
        };

        $q       = $text('q');
        $section = $text('section');
        $state   = in_array($request->query('state'), ['active', 'paused', 'expired'], true) ? $request->query('state') : null;

        $query = AffiliateLink::latest('id');

        if ($q !== '') {
            // % and _ typed by the admin are treated as normal letters
            $like = '%' . addcslashes($q, '\\%_') . '%';
            $query->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('merchant', 'like', $like)->orWhere('description', 'like', $like));
        }
        if ($state) {
            $query->inState($state);
        }
        if ($section !== '') {
            $query->where('section', $section);
        }

        $links = $query->paginate(20)->withQueryString();

        // Clicks in the last 30 days, for the links on this page
        $recent = DB::table('affiliate_clicks')
            ->where('day', '>=', now(config('shop.timezone'))->subDays(29)->toDateString())
            ->whereIn('affiliate_link_id', $links->pluck('id'))
            ->selectRaw('affiliate_link_id, sum(clicks) as total')
            ->groupBy('affiliate_link_id')
            ->pluck('total', 'affiliate_link_id');

        $counts = [
            'all'     => AffiliateLink::count(),
            'active'  => AffiliateLink::inState('active')->count(),
            'paused'  => AffiliateLink::inState('paused')->count(),
            'expired' => AffiliateLink::inState('expired')->count(),
        ];

        $sections = AffiliateLink::whereNotNull('section')->distinct()->orderBy('section')->pluck('section');

        return view('admin.affiliate.index', compact('links', 'recent', 'counts', 'sections', 'q', 'state', 'section'));
    }

    // ---------- ANALYTICS ----------
    public function analytics()
    {
        $tz     = config('shop.timezone');
        $today  = now($tz)->toDateString();
        $from7  = now($tz)->subDays(6)->toDateString();
        $from30 = now($tz)->subDays(29)->toDateString();

        $perDay = DB::table('affiliate_clicks')->where('day', '>=', $from30)
            ->selectRaw('day, sum(clicks) as total')->groupBy('day')->pluck('total', 'day');

        // One number for each of the last 30 days, zeros included
        $series = collect();
        foreach (range(29, 0) as $ago) {
            $day = now($tz)->subDays($ago)->toDateString();
            $series->put($day, (int) ($perDay[$day] ?? 0));
        }

        $totals = [
            'today' => (int) ($perDay[$today] ?? 0),
            'week'  => (int) DB::table('affiliate_clicks')->where('day', '>=', $from7)->sum('clicks'),
            'month' => (int) $series->sum(),
            'all'   => (int) DB::table('affiliate_clicks')->sum('clicks'),
        ];

        // The ten links with the most clicks in the last 30 days
        $top = DB::table('affiliate_clicks as c')
            ->join('affiliate_links as l', 'l.id', '=', 'c.affiliate_link_id')
            ->where('c.day', '>=', $from30)
            ->selectRaw('l.id, l.name, l.merchant, l.section, sum(c.clicks) as clicks30, sum(case when c.day >= ? then c.clicks else 0 end) as clicks7', [$from7])
            ->groupBy('l.id', 'l.name', 'l.merchant', 'l.section')
            ->orderByDesc('clicks30')
            ->limit(10)
            ->get();

        // Clicks in the last 30 days for each section
        $bySection = DB::table('affiliate_clicks as c')
            ->join('affiliate_links as l', 'l.id', '=', 'c.affiliate_link_id')
            ->where('c.day', '>=', $from30)
            ->selectRaw("coalesce(l.section, 'No section') as section_name, sum(c.clicks) as clicks30")
            ->groupByRaw("coalesce(l.section, 'No section')")
            ->orderByDesc('clicks30')
            ->get();

        $needsCheck = AffiliateLink::active()->orderBy('name')->get()->filter(fn (AffiliateLink $link) => $link->needsCheck())->values();
        $max        = (int) $series->max();

        return view('admin.affiliate.analytics', compact('totals', 'series', 'max', 'top', 'bySection', 'needsCheck'));
    }

    // ---------- ADD ----------
    public function create()
    {
        return view('admin.affiliate.create', $this->formData(new AffiliateLink(['status' => 'active'])));
    }

    public function store(AffiliateLinkRequest $request)
    {
        $link = AffiliateLink::create($this->payload($request));
        $this->forgetCaches();

        Audit::record('affiliate.created', $link->name, 'Short name: ' . $link->slug . '. Status: ' . $link->status . '.');

        return redirect()->route('admin.affiliate-links.index')
            ->with('success', '"' . $link->name . '" was added. Shortcode: [[affiliate:' . $link->slug . ']]')
            ->with('alert_title', 'Link saved');
    }

    // ---------- EDIT ----------
    public function edit(AffiliateLink $link)
    {
        return view('admin.affiliate.edit', $this->formData($link));
    }

    public function update(AffiliateLinkRequest $request, AffiliateLink $link)
    {
        $link->update($this->payload($request, $link));
        $this->forgetCaches();

        // Which fields changed (names only: addresses and notes are never written in the log)
        $changed = collect(array_keys($link->getChanges()))->reject(fn ($key) => $key === 'updated_at')->implode(', ');
        Audit::record('affiliate.updated', $link->name, $changed !== '' ? 'Changed: ' . $changed : 'Saved without changes.');

        return redirect()->route('admin.affiliate-links.index')
            ->with('success', '"' . $link->name . '" was updated.')
            ->with('alert_title', 'Link saved');
    }

    // ---------- PAUSE OR RESUME ----------
    public function toggle(AffiliateLink $link)
    {
        $resuming = $link->state() !== 'active';

        // A link whose end date has passed cannot be resumed until the date is changed
        if ($resuming && $link->expires_at && $link->expires_at->toDateString() < now(config('shop.timezone'))->toDateString()) {
            return back()->with('error', 'The end date of "' . $link->name . '" has passed. Edit the link to change the date first.')
                ->with('alert_title', 'Cannot resume');
        }

        $link->update(['status' => $resuming ? 'active' : 'paused']);
        $this->forgetCaches();

        Audit::record($resuming ? 'affiliate.resumed' : 'affiliate.paused', $link->name);

        return back()->with('success', '"' . $link->name . '" is now ' . ($resuming ? 'showing again.' : 'paused. Visitors no longer see it.'))
            ->with('alert_title', $resuming ? 'Link active' : 'Link paused');
    }

    // ---------- "I OPENED THIS LINK AND IT WORKS" ----------
    public function checked(AffiliateLink $link)
    {
        $link->forceFill(['last_checked_at' => now()])->save();

        Audit::record('affiliate.checked', $link->name);

        return back()->with('success', '"' . $link->name . '" is marked as checked today.')->with('alert_title', 'Link checked');
    }

    // ---------- DELETE (admin only) ----------
    public function destroy(Request $request, AffiliateLink $link)
    {
        abort_unless($request->user()->isAdmin(), 403);

        if ($link->image) {
            Storage::disk('public')->delete($link->image);
        }
        $name = $link->name;
        $link->delete();   // its click counters go with it
        $this->forgetCaches();

        Audit::record('affiliate.deleted', $name);

        return redirect()->route('admin.affiliate-links.index')
            ->with('success', '"' . $name . '" was deleted.')
            ->with('alert_title', 'Link deleted');
    }

    // ---------- helpers ----------

    // The footer link and the sitemap are remembered for a few minutes. A change must show at once.
    private function forgetCaches(): void
    {
        Cache::forget('affiliate.has');
        Cache::forget('seo.sitemap');
    }

    private function formData(AffiliateLink $link): array
    {
        return [
            'link'           => $link,
            'sections'       => (array) config('affiliate.sections', []),
            'blogCategories' => BlogCategory::orderBy('sort_order')->get(['id', 'name']),
        ];
    }

    // Turns the checked form values into what is saved
    private function payload(AffiliateLinkRequest $request, ?AffiliateLink $link = null): array
    {
        $data = $request->safe()->only(['name', 'merchant', 'url', 'description', 'section', 'status', 'expires_at', 'blog_category_id']);

        $data['description']      = filled($data['description'] ?? null) ? $data['description'] : null;
        $data['section']          = filled($data['section'] ?? null) ? $data['section'] : null;
        $data['expires_at']       = filled($data['expires_at'] ?? null) ? $data['expires_at'] : null;
        $data['blog_category_id'] = filled($data['blog_category_id'] ?? null) ? (int) $data['blog_category_id'] : null;
        $data['is_featured']      = $request->boolean('is_featured');
        $data['is_direct']        = $request->boolean('is_direct');

        // An empty short name keeps the old one (changing it would break the shortcodes already written)
        $typed        = $request->validated('slug');
        $data['slug'] = filled($typed) ? $typed : ($link?->slug ?? $this->uniqueSlug($data['name']));

        // PHOTO: saved under a random safe name. The uploaded file name is never used.
        if ($request->hasFile('image')) {
            if ($link?->image) {
                Storage::disk('public')->delete($link->image);
            }
            $data['image'] = $request->file('image')->store('affiliate', 'public');
        } elseif ($link && $request->boolean('remove_image')) {
            if ($link->image) {
                Storage::disk('public')->delete($link->image);
            }
            $data['image'] = null;
        }

        return $data;
    }

    // "Cordless Drill" becomes cordless-drill; if taken, cordless-drill-2 ...
    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'link';
        $slug = $base;
        $i    = 2;

        while (AffiliateLink::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}