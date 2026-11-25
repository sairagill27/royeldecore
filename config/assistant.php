<?php

return [
    // The assistant page and the floating button always work. Without a key it runs in "simple mode"
    // (it matches the visitor's words to your products). With a key, an AI writes the answers.
    'enabled'  => (bool) env('ASSISTANT_ENABLED', true),
    'api_key'  => env('GEMINI_API_KEY'),
    'model'    => env('GEMINI_MODEL', 'gemini-3.8-flash'),   // copy the exact name of a free Flash model from Google AI Studio
    'endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models',
    'timeout'  => 25,        // seconds to wait for the AI

    'max_chars' => 500,      // longest question
    'max_turns' => 6,        // how many earlier exchanges are sent along for context

    // Daily caps. They protect the free plan. Each person is counted by login or by internet address.
    'per_person_daily' => (int) env('ASSISTANT_DAILY_PER_PERSON', 30),
    'per_ip_daily'     => (int) env('ASSISTANT_DAILY_PER_IP', 150),     // one internet address (many people can share it)
    'total_daily'      => (int) env('ASSISTANT_DAILY_TOTAL', 800),
];