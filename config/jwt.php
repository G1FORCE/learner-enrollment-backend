<?php

return [
    'secret' => env('JWT_SECRET'),

    // Token lifetime in seconds: 1 hour
    'ttl' => (int) env('JWT_TTL', 3600),

    'issuer' => env('APP_URL', 'http://localhost'),
];
