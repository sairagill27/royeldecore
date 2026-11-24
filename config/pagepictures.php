<?php

return [
    // The picture is cropped to this shape and saved as a light JPG
    'width'  => 2000,
    'height' => 875,
    'max_kb' => 4096,

    // One slot for each page that has a green header. The key must match the page.
    'slots' => [
        'shop' => [
            'label' => 'Shop page',
            'hint'  => 'A styled wall with framed art or a mirror in warm lamp light. Keep the right side interesting and the left side calm.',
        ],
        'blog' => [
            'label' => 'Blog page',
            'hint'  => 'A cosy corner: a reading chair, a plant and a warm lamp, or a neat flat lay of décor items.',
        ],
        'tools' => [
            'label' => 'Décor tools page',
            'hint'  => 'Planning: a measuring tape, paint swatches and a small plant on a wooden table.',
        ],
        'helper' => [
            'label' => 'Décor Helper page',
            'hint'  => 'A mood-board feeling: swatches, small frames and a vase arranged neatly.',
        ],
        'assistant' => [
            'label' => 'Assistant page',
            'hint'  => 'A calm living-room corner with soft light, with nothing busy on the left.',
        ],
        'about' => [
            'label' => 'About page',
            'hint'  => 'A warm studio table: fabric swatches, a brass lamp and a small plant. Keep the left side calm.',
        ],
        'contact' => [
            'label' => 'Contact page',
            'hint'  => 'A welcoming entrance: a walnut console with a lamp, a plant and a round mirror.',
        ],
        'recommended' => [
            'label' => 'Recommended page',
            'hint'  => 'Tools and small extras: a drill, a level and picture hooks on a wooden surface.',
        ],
    ],
];