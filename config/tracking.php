<?php

return [
    'token_length' => env('TRACKING_TOKEN_LENGTH', 40),
    'click_id_prefix' => env('CLICK_ID_PREFIX', 'CLK_'),
    'domain' => env('TRACKING_DOMAIN', null),
];
