<?php

return [
    // Only enable behind a platform proxy that replaces forwarded headers.
    'trust_platform_proxy' => (bool) env('TRUST_PLATFORM_PROXY', false),
    'cron_secret' => env('CRON_SECRET'),
];
