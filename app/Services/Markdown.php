<?php

namespace App\Services;

use Illuminate\Support\Str;

class Markdown
{
    // Turns Markdown text into HTML that is safe to show.
    // Raw HTML typed inside the text is REMOVED, and dangerous links are blanked.
    // Both the editor preview and the public blog use this one function.
    public static function toHtml(?string $text): string
    {
        return Str::markdown((string) $text, [
            'html_input'         => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level'  => 20,
        ]);
    }
}
