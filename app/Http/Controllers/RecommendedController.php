<?php

namespace App\Http\Controllers;

use App\Models\AffiliateLink;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RecommendedController extends Controller
{
    // /recommended: active links grouped by section, with a search box and section filter
    public function index(Request $request)
    {
        // SECURITY: only plain text is accepted (a trick like q[]=x is ignored), at most 80 characters
        $text = function (string $key) use ($request): string {
            $value = $request->query($key);

            return is_string($value) ? Str::limit(Str::squish($value), 80, '') : '';
        };

        $q       = $text('q');
        $section = $text('section');
        $q       = mb_strlen($q) >= 2 ? $q : '';

        // The section buttons: only sections that have an active link
        $sectionNames = AffiliateLink::active()->whereNotNull('section')->distinct()->orderBy('section')->pluck('section');
        $section      = $sectionNames->contains($section) ? $section : '';
        $filtered     = $q !== '' || $section !== '';

        $query = AffiliateLink::active();

        if ($q !== '') {
            // % and _ typed by a visitor are treated as normal letters
            $like = '%' . addcslashes($q, '\\%_') . '%';
            $query->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('merchant', 'like', $like)
                ->orWhere('description', 'like', $like)->orWhere('section', 'like', $like));
        }
        if ($section !== '') {
            $query->where('section', $section);
        }

        $links = $query->orderByRaw('section is null')->orderBy('section')->orderBy('id')->get();

        // Featured picks go on top, and only when nobody is searching. They are not repeated below.
        $featured = $filtered ? collect() : $links->where('is_featured', true)->take(3)->values();
        $rest     = $links->reject(fn (AffiliateLink $link) => $featured->contains('id', $link->id));
        $groups   = $rest->groupBy(fn (AffiliateLink $link) => $link->section ?: 'More picks');

        $hasAny = $filtered ? AffiliateLink::active()->exists() : $links->isNotEmpty();

        return view('recommended.index', compact('groups', 'featured', 'sectionNames', 'q', 'section', 'filtered', 'hasAny', 'links'));
    }
}