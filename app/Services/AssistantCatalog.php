<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

// Finds real products for a question. Used to feed the AI, and to answer on its own in "simple mode".
class AssistantCatalog
{
    // Everyday words that say nothing about a product
    private const STOP = ['the', 'and', 'for', 'with', 'you', 'your', 'are', 'can', 'how', 'what', 'which', 'that', 'this',
        'have', 'has', 'need', 'want', 'looking', 'please', 'show', 'give', 'some', 'any', 'from', 'about', 'best', 'good',
        'nice', 'idea', 'ideas', 'help', 'find', 'get', 'buy', 'shop', 'piece', 'pieces', 'item', 'items', 'something',
        'recommend', 'suggest', 'would', 'could', 'like', 'more', 'room', 'home', 'house'];

    // Only products that can be bought: visible and in stock
    private static function base()
    {
        return Product::select(['id', 'category_id', 'name', 'slug', 'price', 'stock', 'image', 'description', 'is_featured'])
            ->with('category:id,name')
            ->visible()
            ->where('stock', '>', 0);
    }

    // The useful words of a question: 3 or more letters, no everyday words, at most 8
    public static function words(string $text): array
    {
        preg_match_all('/[\p{L}\p{N}]{3,}/u', mb_strtolower($text), $found);

        return collect($found[0])
            ->reject(fn ($word) => in_array($word, self::STOP, true))
            ->unique()
            ->take(8)
            ->values()
            ->all();
    }

    // Products whose name, category or description contain the words, best match first
    public static function matching(string $question, int $limit = 25): Collection
    {
        $words = self::words($question);

        if ($words === []) {
            return collect();
        }

        $found = self::base()->where(function ($query) use ($words) {
            foreach ($words as $word) {
                // % and _ are wildcards: typed by a visitor, they are treated as normal letters
                $like = '%' . addcslashes($word, '\\%_') . '%';
                $query->orWhere('name', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhereHas('category', fn ($c) => $c->where('name', 'like', $like));
            }
        })->take(80)->get();

        return $found->map(function (Product $product) use ($words) {
            $name     = mb_strtolower($product->name);
            $category = mb_strtolower($product->category?->name ?? '');
            $text     = mb_strtolower(strip_tags((string) $product->description));
            $score    = 0;

            foreach ($words as $word) {
                $score += (str_contains($name, $word) ? 3 : 0) + (str_contains($category, $word) ? 2 : 0) + (str_contains($text, $word) ? 1 : 0);
            }
            $product->match_score = $score;

            return $product;
        })
            ->sortByDesc(fn (Product $p) => [$p->match_score, (int) $p->is_featured, $p->id])
            ->take($limit)
            ->values();
    }

    // What the AI may talk about: the matches, filled up with featured and newest products
    public static function context(string $question, int $limit = 25): Collection
    {
        $items = self::matching($question, $limit);

        if ($items->count() < $limit) {
            $more = self::base()
                ->whereNotIn('id', $items->pluck('id'))
                ->orderByDesc('is_featured')
                ->latest('id')
                ->take($limit - $items->count())
                ->get();

            $items = $items->concat($more)->values();
        }

        return $items;
    }

    // One line per product for the AI: slug | name | category | price | short note
    public static function line(Product $product): string
    {
        $clean = fn (string $text) => str_replace('|', '/', Str::squish($text));
        $note  = Str::limit(Str::squish(strip_tags((string) $product->description)), 120);

        return implode(' | ', [
            $product->slug,
            $clean($product->name),
            $clean($product->category?->name ?? 'General'),
            self::price($product),
            $clean($note),
        ]);
    }

    public static function price(Product $product): string
    {
        return config('shop.currency', 'Rs.') . ' ' . number_format($product->price);
    }

    // What the chat shows for a product (everything comes from the database, never from the AI)
    public static function card(Product $product): array
    {
        return [
            'name'     => $product->name,
            'price'    => self::price($product),
            'url'      => route('products.show', $product->slug),
            'image'    => $product->image ? asset('storage/' . $product->image) : null,
            'category' => $product->category?->name,
        ];
    }

    // The answer in "simple mode": no AI, just the products that match the words
    public static function simple(string $question): array
    {
        $found     = self::matching($question, 4);
        $followups = Category::where('is_active', true)->orderBy('sort_order')->take(3)->pluck('name')
            ->map(fn ($name) => 'Show me ' . $name)->all();

        $reply = $found->isNotEmpty()
            ? 'Here are pieces from our shop that match your words. For ideas by room, style and budget, try the Décor Helper, or message us and we will help you choose.'
            : 'I could not find a piece for those words. Try a product type such as "wall clock" or "mirror", use the Décor Helper, or message us and we will help you choose.';

        return ['reply' => $reply, 'products' => $found, 'followups' => $followups];
    }
}