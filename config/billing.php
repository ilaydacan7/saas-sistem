<?php

declare(strict_types=1);

return [

    'gateway' => env('BILLING_GATEWAY', 'manual'),

    'currency' => env('BILLING_CURRENCY', 'TRY'),

    'trial_days' => (int) env('BILLING_TRIAL_DAYS', 14),

];
