<?php

return [
    'ip_whitelist_enabled' => env('POSTBACK_IP_WHITELIST_ENABLED', true),
    'signature_header' => env('POSTBACK_SIGNATURE_HEADER', 'X-Postback-Signature'),
    'global_secret' => env('POSTBACK_SECRET', null),
    'rate_limit' => env('POSTBACK_RATE_LIMIT', 60), // Requests per minute per IP
];
