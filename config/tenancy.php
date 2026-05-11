<?php

return [
    'resolve_by_domain' => env('TENANCY_RESOLVE_BY_DOMAIN', false),
    'base_domain' => env('TENANCY_BASE_DOMAIN', ''),
    'allow_custom_domains' => env('TENANCY_ALLOW_CUSTOM_DOMAINS', true),
];
