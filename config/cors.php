<?php

return [
    'paths' => ['api/v1/*'],
    'allowed_methods' => ['GET', 'HEAD', 'POST', 'OPTIONS'],
    'allowed_origins' => array_values(array_filter([env('FRONTEND_URL')])),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Accept', 'Content-Type'],
    'exposed_headers' => ['Content-Language'],
    'max_age' => 600,
    'supports_credentials' => false,
];
