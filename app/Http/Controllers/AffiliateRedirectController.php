<?php

namespace App\Http\Controllers;

use App\Models\AffiliateLink;
use App\Services\Audit;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AffiliateRedirectController extends Controller
{
    // /go/{slug}: counts the click, then sends the visitor to the store.
    // Nothing from the visitor decides where they go: the address is the one an admin saved.
    public function __invoke(Request $request, string $slug)
    {
        // Only links that are Active, and not past their end date
        $link = AffiliateLink::active()->where('slug', $slug)->first();

        abort_if(! $link, 404);

        // The address is checked again here, even though it was checked when it was saved
        if (! AffiliateLink::safeUrl($link->url)) {
            // Written to the activity log at most once an hour, so a busy link cannot fill it
            if (Cache::add('affblock:' . $link->id, 1, now()->addHour())) {
                Audit::record('affiliate.blocked', $link->name, 'A saved address failed the safety check, so visitors were not sent on. Edit the link.');
            }

            abort(404);
        }

        if ($this->isCountable($request)) {
            $this->count($request, $link);
        }

        // 302 = temporary, so the store can change and search engines never keep this address as the page.
        // no-store makes every click reach this code, so every click is counted.
        return redirect()->away($link->url, 302, [
            'X-Robots-Tag'  => 'noindex, nofollow',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    // People count. Robots, link previews and tools do not.
    private function isCountable(Request $request): bool
    {
        $agent = (string) $request->userAgent();

        return $request->isMethod('GET')
            && $agent !== ''
            && ! preg_match((string) config('affiliate.bot_pattern'), $agent);
    }

    // A counter per link and per day (no IP address, no cookie). A problem here never stops the visitor.
    private function count(Request $request, AffiliateLink $link): void
    {
        try {
            // The same visitor clicking again within a few minutes counts once. Only a scrambled marker
            // that cannot be turned back into an address is kept, and only until it expires.
            $visitor = hash_hmac('sha256', $request->ip() . '|' . $request->userAgent(), (string) config('app.key'));
            $minutes = (int) config('affiliate.dedupe_minutes', 30);

            if (! Cache::add('affclick:' . $link->id . ':' . $visitor, 1, now()->addMinutes($minutes))) {
                return;
            }

            $day = now(config('shop.timezone'))->toDateString();
            $row = DB::table('affiliate_clicks')->where('affiliate_link_id', $link->id)->where('day', $day);

            if ((clone $row)->increment('clicks') === 0) {
                try {
                    DB::table('affiliate_clicks')->insert(['affiliate_link_id' => $link->id, 'day' => $day, 'clicks' => 1]);
                } catch (UniqueConstraintViolationException) {
                    $row->increment('clicks');   // two visitors at the same moment
                }
            }

            AffiliateLink::whereKey($link->id)->increment('clicks_total');
        } catch (\Throwable $e) {
            report($e);
        }
    }
}