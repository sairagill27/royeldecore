<?php

namespace App\Services;

class JsonLd
{
    // Builds a complete <script type="application/ld+json"> tag from an array.
    // The JSON_HEX flags turn < > & ' " inside the text into codes like \u003C, so a title
    // such as </script> can never end the tag early. Bad characters are replaced, not fatal.
    public static function tag(array $data): string
    {
        $json = json_encode(
            $data,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
                | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE
        ) ?: '{}';

        return '<script type="application/ld+json">' . $json . '</script>';
    }
}