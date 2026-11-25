<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

// An answer that the shop wrote itself. It is used before any AI, costs nothing and never makes things up.
class AssistantAnswer extends Model
{
    protected $fillable = ['question', 'keywords', 'answer', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // Lowercase, with everything except letters and numbers turned into single spaces
    public static function normalize(string $text): string
    {
        return trim((string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', mb_strtolower($text)));
    }

    // The best saved answer for a visitor's message, or null.
    // 1. A message that is exactly a saved question (the suggestion buttons) always wins.
    // 2. Otherwise each keyword or phrase found as WHOLE words adds points (a longer phrase adds more).
    public static function match(string $message): ?self
    {
        $text = self::normalize($message);

        if ($text === '') {
            return null;
        }

        $padded = ' ' . $text . ' ';
        $best   = null;
        $score  = 0;

        foreach (self::active()->orderBy('id')->get() as $item) {
            if ($text === self::normalize($item->question)) {
                return $item;
            }

            $points = 0;
            foreach (explode(',', (string) $item->keywords) as $phrase) {
                $phrase = self::normalize($phrase);

                if (mb_strlen($phrase) >= 2 && str_contains($padded, ' ' . $phrase . ' ')) {
                    $points += substr_count($phrase, ' ') + 1;
                }
            }

            if ($points > $score) {
                $best  = $item;
                $score = $points;
            }
        }

        return $best;
    }

    // Up to three other questions, used as follow-up buttons
    public static function suggest(int $exceptId): array
    {
        return self::active()->where('id', '!=', $exceptId)->orderByDesc('hits')->orderBy('id')->take(3)->pluck('question')->all();
    }
}