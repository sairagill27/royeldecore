<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

// Matches a room, a style and a budget to real products. No AI and no outside service: it is free and instant.
class DecorHelper
{
    // Returns ['products' => up to 8 products, 'board' => up to 4 with photos, 'matched' => true if some words matched]
    public static function suggest(string $room, string $style, string $budget): array
    {
        $roomWords  = (array) config("helper.rooms.{$room}.words", []);
        $styleWords = (array) config("helper.styles.{$style}.words", []);
        $limits     = (array) config("helper.budgets.{$budget}", []);

        // Only products that can be bought: visible, in stock, inside the budget
        $query = Product::select(['id', 'category_id', 'name', 'slug', 'price', 'stock', 'image', 'description', 'is_featured'])
            ->with('category:id,name')
            ->visible()
            ->where('stock', '>', 0);

        if (isset($limits['min'])) {
            $query->where('price', '>=', $limits['min']);
        }
        if (isset($limits['max'])) {
            $query->where('price', '<=', $limits['max']);
        }

        // The 150 newest are scored (plenty for a small shop)
        $scored = $query->latest()->take(150)->get()->map(function (Product $product) use ($roomWords, $styleWords) {
            $text  = mb_strtolower($product->name . ' ' . ($product->category?->name ?? '') . ' ' . strip_tags((string) $product->description));
            $score = 0;

            foreach ($styleWords as $word) {
                $score += self::has($text, $word) ? 3 : 0;   // the style matters most
            }
            foreach ($roomWords as $word) {
                $score += self::has($text, $word) ? 2 : 0;
            }

            $product->match_score = $score;

            return $product;
        });

        $matched = $scored->contains(fn (Product $p) => $p->match_score > 0);

        // Best match first. Ties: featured, then has a photo, then newest.
        $products = $scored
            ->sortByDesc(fn (Product $p) => [$p->match_score, (int) $p->is_featured, (int) filled($p->image), $p->id])
            ->take(8)
            ->values();

        return [
            'products' => $products,
            'board'    => self::board($products),
            'matched'  => $matched,
        ];
    }

    // The mood board: the first four matches that have a photo
    private static function board(Collection $products): Collection
    {
        return $products->filter(fn (Product $p) => filled($p->image))->take(4)->values();
    }

    // Does the text contain this word? (a word that STARTS with it counts, so "mirror" finds "mirrors")
    private static function has(string $text, string $word): bool
    {
        return preg_match('/\b' . preg_quote(mb_strtolower($word), '/') . '/u', $text) === 1;
    }
}