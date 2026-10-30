<?php

// The decor tools. "ready" => false shows a "coming soon" page instead of the calculator.
// "view" is the name of the view file (resources/views/tools/<view>.blade.php).
// "gets" is the short list shown in the header ("what you will get").
return [

    'paint-calculator' => [
        'name'        => 'Paint Calculator',
        'short'       => 'Work out how much paint your room needs.',
        'description' => 'Free paint calculator: enter your room size, doors, windows and coats to estimate the litres of paint and the number of tins you need.',
        'icon'        => 'bi-palette',
        'view'        => 'paint',
        'ready'       => true,
        'gets'        => ['Litres of paint', 'Tins to buy', 'Area to paint'],
    ],

    'flooring-calculator' => [
        'name'        => 'Flooring Calculator',
        'short'       => 'Find out how much flooring to buy, with an allowance for cuts.',
        'description' => 'Free flooring calculator: enter your room size and waste allowance to estimate the area of flooring and the number of packs you need.',
        'icon'        => 'bi-grid-3x3-gap',
        'view'        => 'flooring',
        'ready'       => true,
        'gets'        => ['Area to buy', 'Number of packs', 'Estimated cost'],
    ],

    'wallpaper-calculator' => [
        'name'        => 'Wallpaper Calculator',
        'short'       => 'Count the rolls of wallpaper for your walls.',
        'description' => 'Free wallpaper calculator: estimate how many rolls of wallpaper your room needs.',
        'icon'        => 'bi-card-image',
        'view'        => 'wallpaper',
        'ready'       => true,
        'gets'        => ['Rolls of wallpaper', 'Wall area'],
    ],

    'curtain-calculator' => [
        'name'        => 'Curtain Calculator',
        'short'       => 'Estimate the fabric and panels for your windows.',
        'description' => 'Free curtain calculator: estimate the fabric width, length and number of panels for your window.',
        'icon'        => 'bi-window',
        'view'        => 'curtain',
        'ready'       => true,
        'gets'        => ['Fabric width and length', 'Number of panels'],
    ],

    'room-budget-planner' => [
        'name'        => 'Room Budget Planner',
        'short'       => 'Plan your room spending and see what is left.',
        'description' => 'Free room budget planner: list the items for your room, add their costs and see your total and what remains of your budget.',
        'icon'        => 'bi-wallet2',
        'view'        => 'budget',
        'ready'       => true,
        'gets'        => ['Total spend', 'Money left', 'Share by item'],
    ],

];