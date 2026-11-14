<?php

return [
    // Added to every affiliate link. Google asks for "sponsored" on paid and affiliate links.
    'rel' => 'sponsored nofollow noopener',

    // Visitors that look like robots, link previews or tools are sent on, but not counted as clicks.
    'bot_pattern' => '/bot|crawl|spider|slurp|preview|monitor|headless|curl|wget|python|java|facebookexternalhit|whatsapp/i',

    // One visitor clicking the same link again within this many minutes counts once.
    'dedupe_minutes' => 30,

    // A link that nobody has checked for this many days is listed as "Check link".
    'recheck_days' => 30,

    // The sections of the Recommended page (the list in the admin form). Edit it freely.
    // Tip: links to things you also sell yourself send visitors to another store, so you lose that sale.
    'sections' => [
        'Islamic Calligraphy & Wall Art',
        'Acrylic Wall Decor & Mirrors',
        'Designer Wall Clocks',
        'Custom Name Signs & Neon Decor',
        'Wooden & MDF Home Decor',
        'Decorative Lighting',
        'Wall Panels & Decorative Accessories',
    ],
];