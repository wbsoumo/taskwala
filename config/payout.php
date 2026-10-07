<?php

return [
    'currency' => env('PAYOUT_CURRENCY', 'INR'),
    'min_customer_payout' => env('MIN_CUSTOMER_PAYOUT', 0.00),
    'auto_approve_conversions' => env('AUTO_APPROVE_CONVERSIONS', false),
];
