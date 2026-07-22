<?php

declare(strict_types=1);

test('the complete quality gate includes the production frontend build', function () {
    /** @var array{scripts: array<string, list<string>>} $composer */
    $composer = json_decode(
        (string) file_get_contents(dirname(__DIR__, 2).'/composer.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($composer['scripts']['ci:check'])
        ->toContain('npm run build');
});

test('the TypeScript check generates ignored Wayfinder modules on a fresh checkout', function () {
    /** @var array{scripts: array<string, string>} $package */
    $package = json_decode(
        (string) file_get_contents(dirname(__DIR__, 2).'/package.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($package['scripts']['wayfinder:generate'])
        ->toBe('php artisan wayfinder:generate --with-form --no-interaction')
        ->and($package['scripts']['types:check'])
        ->toStartWith('npm run wayfinder:generate && ');
});
