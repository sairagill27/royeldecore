<?php

namespace App\Services;

use Illuminate\Support\Str;

class Markdown
{
    // Turns Markdown text into HTML that is safe to show.
    // Raw HTML typed inside the text is REMOVED, and dangerous links are blanked.
    public static function toHtml(?string $text): string
    {
        return Str::markdown((string) $text, [
            'html_input'         => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level'  => 20,
        ]);
    }

    // For the public article page: the same safe HTML, plus SEO helpers.
    // Returns ['html' => '...', 'toc' => [['id' => 'one', 'text' => 'One', 'level' => 2], ...]]
    public static function article(?string $text): array
    {
        $html = self::toHtml($text);

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

        // 3. Pictures inside the text load lazily (faster page)
        $html = preg_replace('/<img /i', '<img loading="lazy" decoding="async" ', $html);

        return ['html' => $html, 'toc' => $toc];
    }
}
