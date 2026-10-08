<?php

// Shop settings in one place. The numbers below are PLACEHOLDERS: change them
// to your real business rules.
return [
    'currency'           => 'Rs.',
    'shipping_fee'       => 250,     // flat delivery fee
    'free_shipping_over' => 15000,   // orders from this amount ship free (0 = never free)
    'order_prefix'       => 'SGH',   // start of every order number
    'timezone'           => 'Asia/Karachi',   // used to display dates

    // Used only by the AdminSeeder. The real values live in .env
    'admin_email'    => env('ADMIN_EMAIL'),
    'admin_password' => env('ADMIN_PASSWORD'),
];
