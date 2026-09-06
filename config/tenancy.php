<?php

declare(strict_types=1);

return [

    'central_domains' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('TENANCY_CENTRAL_DOMAINS', 'localhost,127.0.0.1'))
    ))),

    'base_domain' => env('TENANCY_BASE_DOMAIN', 'saas.local'),

    'resolvers' => ['domain', 'subdomain'],

    'reserved_subdomains' => [
        'www', 'api', 'admin', 'app', 'mail', 'static', 'cdn', 'assets',
        'status', 'docs', 'blog', 'help', 'support',
    ],

    'reserved_slugs' => [
        'superadmin', 'system', 'root', 'billing', 'webhook', 'webhooks',
        'login', 'register', 'password', 'health', 'up', 'metrics',
    ],

    'strict' => (bool) env('TENANCY_STRICT', true),

];
