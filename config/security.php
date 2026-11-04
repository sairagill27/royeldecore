<?php

return [
    // true = every admin and staff account must set up two-step login before using the admin area.
    // Keep it false while you are building. Switch it on in the live server's .env.
    'two_factor_required' => (bool) env('TWO_FACTOR_REQUIRED', false),
];