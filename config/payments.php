<?php

return [
    // This project deliberately supports simulated payments only.
    'driver' => env('PIX_DRIVER', 'local'),
    'production_demo_enabled' => env('PIX_DEMO_ENABLED', false),
    'abacate_key' => env('ABACATEPAY_DEV_KEY'),
    'expires_in' => 900,
];
