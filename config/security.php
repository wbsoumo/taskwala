<?php

return [
    'login_max_attempts' => env('SECURITY_LOGIN_MAX_ATTEMPTS', 5),
    'login_decay_minutes' => env('SECURITY_LOGIN_DECAY_MINUTES', 1),
    'trusted_proxies' => env('TRUSTED_PROXIES', null),
    'audit_logging' => env('AUDIT_LOGGING_ENABLED', true),
];
