<?php

declare(strict_types=1);

$applicationHost = parse_url((string) env('APP_URL', ''), PHP_URL_HOST);

return [
    'trusted_proxies' => env('TRUSTED_PROXIES'),
    'allowed_hosts' => is_string($applicationHost) && $applicationHost !== ''
        ? [
            '^'.preg_quote($applicationHost).'$',
            '^\d{1,3}(?:\.\d{1,3}){3}$',
        ]
        : [],
];
