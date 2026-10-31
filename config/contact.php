<?php

// Public contact details. Leave a value empty and it is simply not shown anywhere on the site.
// Fill them in your .env file (see the CONTACT_ lines).
return [
    'email'    => env('CONTACT_EMAIL'),      // for example hello@royeldecore.com
    'phone'    => env('CONTACT_PHONE'),      // as you want it shown, for example +92 300 1234567
    'whatsapp' => env('CONTACT_WHATSAPP'),   // with the country code, for example 923001234567
    'address'  => env('CONTACT_ADDRESS'),
    'hours'    => env('CONTACT_HOURS'),      // for example Mon-Sat, 10am to 7pm
];