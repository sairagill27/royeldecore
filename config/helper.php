<?php

// The Decor Helper: the choices visitors can make, and the words used to match products.
// "words" are matched against the START of words in a product's name, category and description
// (so "mirror" also finds "mirrors"). Change them freely to suit your products.
return [

    'rooms' => [
        'living' => [
            'label' => 'Living room', 'icon' => 'bi-lamp',
            'words' => ['clock', 'mirror', 'calligraphy', 'canvas', 'wall art', 'frame', 'panel'],
            'tool'  => 'paint-calculator',
            'tips'  => [
                'Hang art so its centre is at eye level, about 145 cm from the floor.',
                'A mirror placed across from a window bounces daylight around the room.',
                'One large piece usually looks calmer than many small ones.',
            ],
        ],
        'bedroom' => [
            'label' => 'Bedroom', 'icon' => 'bi-moon',
            'words' => ['mirror', 'neon', 'name', 'canvas', 'floral', 'calm', 'frame'],
            'tool'  => 'curtain-calculator',
            'tips'  => [
                'Choose one calm focal wall, usually the one behind the bed.',
                'Warm, soft light makes a bedroom feel restful.',
                'A personalised name sign adds a personal touch above a dresser or bed.',
            ],
        ],
        'dining' => [
            'label' => 'Dining area', 'icon' => 'bi-cup-hot',
            'words' => ['clock', 'mirror', 'calligraphy', 'plate', 'panel', 'canvas'],
            'tool'  => 'room-budget-planner',
            'tips'  => [
                'A piece above a sideboard looks best at about two thirds of the furniture width.',
                'Keep the centre of a wall piece roughly 15 to 25 cm above the furniture below it.',
                'Calligraphy and clocks suit dining walls well.',
            ],
        ],
        'entrance' => [
            'label' => 'Entrance and hall', 'icon' => 'bi-door-open',
            'words' => ['mirror', 'name', 'welcome', 'sign', 'clock', 'calligraphy'],
            'tool'  => 'paint-calculator',
            'tips'  => [
                'A mirror near the door makes a small entrance feel larger and brighter.',
                'A name sign tells guests they have arrived at your home.',
                'Keep the entrance simple: one or two pieces are enough.',
            ],
        ],
        'study' => [
            'label' => 'Study or office', 'icon' => 'bi-laptop',
            'words' => ['clock', 'canvas', 'motivational', 'frame', 'organizer', 'quote'],
            'tool'  => 'room-budget-planner',
            'tips'  => [
                'Choose calm colours for the wall you face while working.',
                'A wall clock keeps time in view without a phone.',
                'An organiser keeps the desk clear, which helps you focus.',
            ],
        ],
        'kids' => [
            'label' => 'Kids room', 'icon' => 'bi-stars',
            'words' => ['name', 'neon', 'cartoon', 'star', 'moon', 'sign', 'kids'],
            'tool'  => 'wallpaper-calculator',
            'tips'  => [
                'A name sign makes a room feel like their own.',
                'Hang pieces at a height your child can see and enjoy.',
                'Soft night-light style pieces make bedtime calmer.',
            ],
        ],
    ],

    'styles' => [
        'modern' => [
            'label' => 'Modern', 'icon' => 'bi-bounding-box',
            'words' => ['modern', 'abstract', 'acrylic', 'geometric', 'designer', 'contemporary'],
            'tip'   => 'Keep to two or three colours and let one bold piece lead.',
        ],
        'islamic' => [
            'label' => 'Islamic and traditional', 'icon' => 'bi-moon-stars',
            'words' => ['islamic', 'calligraphy', 'allah', 'muhammad', 'quran', 'arabic', 'bismillah', 'ayat', 'masjid', 'mosque'],
            'tip'   => 'Hang calligraphy at eye level or higher, in a clean and quiet spot.',
        ],
        'minimal' => [
            'label' => 'Minimal', 'icon' => 'bi-dash-circle',
            'words' => ['minimal', 'simple', 'clean', 'line art', 'mono', 'scandi'],
            'tip'   => 'Leave empty wall around each piece. Space is part of the design.',
        ],
        'golden' => [
            'label' => 'Warm and golden', 'icon' => 'bi-brightness-high',
            'words' => ['gold', 'brass', 'luxury', 'neon', 'glow', 'warm'],
            'tip'   => 'Gold looks best against soft, warm walls with plenty of light.',
        ],
        'natural' => [
            'label' => 'Natural and green', 'icon' => 'bi-flower2',
            'words' => ['plant', 'wood', 'leaf', 'leaves', 'floral', 'flower', 'botanical', 'natural', 'green'],
            'tip'   => 'Pair green and wood tones with creamy neutrals for a fresh feel.',
        ],
    ],

    // Budget choices. The labels are built from the shop currency on the page.
    'budgets' => [
        'any'  => [],
        'low'  => ['max' => 5000],
        'mid'  => ['min' => 5000, 'max' => 15000],
        'high' => ['min' => 15000],
    ],
];