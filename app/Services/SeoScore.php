<?php

namespace App\Services;

use App\Models\BlogPost;
use Illuminate\Support\Str;

class SeoScore
{
    // Score a saved post (used by the SEO audit page)
    public static function forPost(BlogPost $post): array
    {
        return self::analyze([
            'title'            => $post->title,
            'seo_title'        => $post->seo_title,
            'meta_description' => $post->meta_description,
            'focus_keyword'    => $post->focus_keyword,
            'slug'             => $post->slug,
            'content'          => $post->content,
            'has_image'        => filled($post->featured_image),
            'image_alt'        => $post->featured_image_alt,
        ]);
    }

    // 80 and above = good, 50 to 79 = okay, below 50 = needs work
    public static function level(int $score): string
    {
        return $score >= 80 ? 'good' : ($score >= 50 ? 'ok' : 'bad');
    }

    // Eleven checks worth 100 points in total. Each check says what to fix when it fails.
    public static function analyze(array $d): array
    {
        $keyword  = Str::squish((string) ($d['focus_keyword'] ?? ''));
        $titleTag = trim((string) (($d['seo_title'] ?? '') ?: ($d['title'] ?? '')));   // the title Google shows
        $meta     = trim((string) ($d['meta_description'] ?? ''));
        $slug     = (string) ($d['slug'] ?? '');
        $raw      = (string) ($d['content'] ?? '');

        // The article as plain text: Markdown turned into HTML (raw HTML removed), then tags stripped
        $plain  = Str::squish(html_entity_decode(strip_tags(Markdown::toHtml($raw)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $tokens = preg_split('/\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $words  = count($tokens);
        $intro  = implode(' ', array_slice($tokens, 0, 100));

        $headings = preg_match_all('/^#{1,3}\s+\S/m', $raw);

        // A link to another page of this site: "](/shop)" or "](https://your-domain/shop)".
        // "](//other.com)" is NOT internal.
        $base     = preg_quote(rtrim(url('/'), '/'), '~');
        $noImages = preg_replace('/!\[[^\]]*\]\([^)]*\)/', '', $raw);   // pictures are not links
        $internal = preg_match('~\]\((?:/(?!/)|' . $base . ')[^)\s]*\)~i', $noImages) === 1;

        $has    = fn(string $text) => $keyword !== '' && mb_stripos($text, $keyword) !== false;
        $kwSlug = Str::slug($keyword);
        $needKw = 'Set a focus keyword first.';

        $titleLen = mb_strlen($titleTag);
        $metaLen  = mb_strlen($meta);

        $checks = [
            [
                'Focus keyword is set',
                10,
                $keyword !== '',
                'Type the word or short phrase this article is about.'
            ],
            [
                'Keyword is in the title',
                10,
                $has($titleTag),
                $keyword === '' ? $needKw : 'Use the keyword in the title.'
            ],
            [
                'Title is 30 to 60 characters',
                10,
                $titleLen >= 30 && $titleLen <= 60,
                "The title is {$titleLen} characters. Aim for 30 to 60."
            ],
            [
                'Meta description is 120 to 160 characters',
                10,
                $metaLen >= 120 && $metaLen <= 160,
                "The description is {$metaLen} characters. Aim for 120 to 160."
            ],
            [
                'Keyword is in the meta description',
                10,
                $has($meta),
                $keyword === '' ? $needKw : 'Use the keyword in the meta description.'
            ],
            [
                'Keyword is in the first 100 words',
                10,
                $has($intro),
                $keyword === '' ? $needKw : 'Mention the keyword near the start of the article.'
            ],
            [
                'Article is at least 600 words',
                10,
                $words >= 600,
                "The article has {$words} words. Longer, useful articles tend to do better."
            ],
            [
                'Has at least 2 subheadings',
                5,
                $headings >= 2,
                'Break the article up with ## subheadings.'
            ],
            [
                'Featured photo has a description',
                10,
                ! empty($d['has_image']) && filled($d['image_alt'] ?? null),
                'Add a featured photo and describe it in a few words.'
            ],
            [
                'Keyword is in the web address',
                5,
                $kwSlug !== '' && str_contains($slug, $kwSlug),
                $keyword === '' ? $needKw : 'Put the keyword in the slug, for example ' . $kwSlug . '-ideas.'
            ],
            [
                'Links to another page on your site',
                10,
                $internal,
                'Link to a product or another article, for example [our mirrors](/shop/mirrors).'
            ],
        ];

        $score  = 0;
        $result = [];
        foreach ($checks as [$label, $points, $ok, $tip]) {
            $score   += $ok ? $points : 0;
            $result[] = ['label' => $label, 'points' => $points, 'ok' => (bool) $ok, 'tip' => $tip];
        }

        return ['score' => $score, 'checks' => $result, 'words' => $words];
    }
}
