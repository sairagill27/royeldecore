<?php

namespace App\Services;

use App\Models\AffiliateLink;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;

class Markdown
{
    // [[affiliate:short-name]] on its own line becomes a product card (see the Affiliate links page in the admin panel)
    private const SHORTCODE = '/\[\[affiliate:([a-z0-9-]{1,80})\]\]/';

    // Turns Markdown text into HTML that is safe to show.
    // Raw HTML typed inside the text is REMOVED, and dangerous links are blanked.
    public static function toHtml(?string $text): string
    {
        // A shortcode means nothing in plain places (summaries, search, feeds), so it is removed there
        $text = (string) preg_replace(self::SHORTCODE, '', (string) $text);

        return Str::markdown($text, [
            'html_input'         => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level'  => 20,
        ]);
    }

    // For the public article page and the editor preview: the same safe HTML, plus helpers.
    // Returns ['html' => '...', 'toc' => [['id' => 'one', 'text' => 'One', 'level' => 2], ...]]
    public static function article(?string $text): array
    {
        // 0. Affiliate shortcodes become plain placeholders first (at most 8 per article)
        $slugs = [];
        $raw   = (string) preg_replace_callback(self::SHORTCODE, function (array $m) use (&$slugs) {
            if (count($slugs) >= 8) {
                return '';
            }
            $slugs[] = $m[1];

            return 'SGHAFFILIATESLOT' . (count($slugs) - 1) . 'END';
        }, (string) $text);

        $html = self::toHtml($raw);

        // 1. One H1 per page: the article title is the H1, so "# heading" in the text becomes an H2
        $html = preg_replace('/<(\/?)h1(?=[\s>])/i', '<$1h2', $html);

        // 2. Give every H2 and H3 an id (for the contents box and jump links)
        $toc  = [];
        $used = [];
        $html = preg_replace_callback('/<h([23])>(.*?)<\/h\1>/is', function (array $m) use (&$toc, &$used) {
            $label = trim(html_entity_decode(strip_tags($m[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            // The id is made only of a-z, 0-9 and dashes, so it is always safe
            $base = Str::slug($label) ?: 'section';
            $id   = $base;
            $i    = 2;
            while (isset($used[$id])) {
                $id = $base . '-' . $i++;
            }
            $used[$id] = true;

            $toc[] = ['id' => $id, 'text' => $label, 'level' => (int) $m[1]];

            return '<h' . $m[1] . ' id="' . $id . '">' . $m[2] . '</h' . $m[1] . '>';
        }, $html);

        // 3. A picture alone in its paragraph becomes a figure:
        //    ![description](/storage/x.jpg#left "caption")
        //    #left, #right, #center or #full (default) sets the position; the title is the caption.
        //    The values below come from the Markdown parser, which has already escaped them.
        $html = preg_replace_callback('/<p>\s*(<img\b[^>]*>)\s*<\/p>/i', function (array $m): string {
            $tag  = $m[1];
            $attr = function (string $name) use ($tag): string {
                return preg_match('/\b' . $name . '="([^"]*)"/i', $tag, $a) === 1 ? $a[1] : '';
            };

            $src     = $attr('src');
            $alt     = $attr('alt');
            $caption = $attr('title');
            $align   = 'full';

            if (preg_match('/#(full|center|left|right)$/', $src, $a) === 1) {
                $align = $a[1];
                $src   = substr($src, 0, -strlen($a[0]));
            }

            // An unsafe address was blanked by the parser: leave the paragraph as it is
            if ($src === '') {
                return $m[0];
            }

            return '<figure class="sgh-figure sgh-figure--' . $align . '">'
                . '<img loading="lazy" decoding="async" src="' . $src . '" alt="' . $alt . '">'
                . ($caption !== '' ? '<figcaption>' . $caption . '</figcaption>' : '')
                . '</figure>';
        }, $html);

        // 4. Any other picture inside the text loads lazily (faster page)
        $html = preg_replace('/<img (?![^>]*\bloading=)/i', '<img loading="lazy" decoding="async" ', $html);

        // 5. Affiliate cards. Only active links with a safe address become cards. Anything else leaves nothing behind.
        $cards = 0;
        if ($slugs !== []) {
            $links = AffiliateLink::active()->whereIn('slug', array_unique($slugs))->get()->keyBy('slug');

            foreach ($slugs as $i => $slug) {
                $link = $links->get($slug);

                if ($link && AffiliateLink::safeUrl($link->url)) {
                    $card = Blade::render('<x-affiliate-card :link="$link" variant="wide" />', ['link' => $link]);
                    $html = str_replace('<p>SGHAFFILIATESLOT' . $i . 'END</p>', $card, $html, $count);
                    $cards += $count;
                }
            }
        }
        $html = (string) preg_replace('/SGHAFFILIATESLOT\d+END/', '', $html);

        // 6. An article with affiliate cards starts with a clear notice
        if ($cards > 0) {
            $html = Blade::render('<x-affiliate-disclosure />') . $html;
        }

        return ['html' => $html, 'toc' => $toc];
    }
}