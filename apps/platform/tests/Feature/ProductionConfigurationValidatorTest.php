<?php

declare(strict_types=1);

use App\Services\Production\ProductionConfigurationValidator;

beforeEach(function (): void {
    $this->app['env'] = 'production';

    config([
        'app.debug' => false,
        'app.url' => 'https://app.example.com',
        'broadcasting.default' => 'reverb',
        'cache.default' => 'redis',
        'database.redis.cache.scheme' => 'tls',
        'database.redis.default.scheme' => 'tls',
        'filesystems.default' => 's3',
        'filesystems.disks.s3.report' => true,
        'filesystems.disks.s3.throw' => true,
        'mail.default' => 'ses',
        'mail.from.address' => 'support@example.com',
        'ocr.driver' => 'textract',
        'production.allowed_hosts' => ['app.example.com'],
        'production.trusted_proxies' => 'REMOTE_ADDR',
        'queue.default' => 'redis',
        'session.driver' => 'redis',
        'session.domain' => 'app.example.com',
        'session.encrypt' => true,
        'session.secure' => true,
    ]);
});

test('safe production configuration passes validation', function (): void {
    app(ProductionConfigurationValidator::class)->validate();

    expect(true)->toBeTrue();
});

test('production rejects unsafe configuration', function (string $key, mixed $value): void {
    config([$key => $value]);

    expect(fn () => app(ProductionConfigurationValidator::class)->validate())
        ->toThrow(LogicException::class, $key);
})->with([
    'debug mode' => ['app.debug', true],
    'non-HTTPS application URL' => ['app.url', 'http://app.example.com'],
    'non-Redis cache' => ['cache.default', 'file'],
    'non-Redis queue' => ['queue.default', 'sync'],
    'unencrypted Redis cache' => ['database.redis.cache.scheme', 'tcp'],
    'unencrypted Redis connection' => ['database.redis.default.scheme', 'tcp'],
    'non-Redis sessions' => ['session.driver', 'file'],
    'unencrypted sessions' => ['session.encrypt', false],
    'insecure session cookie' => ['session.secure', false],
    'wrong session domain' => ['session.domain', 'example.com'],
    'local document storage' => ['filesystems.default', 'local'],
    'suppressed storage exceptions' => ['filesystems.disks.s3.throw', false],
    'unreported storage exceptions' => ['filesystems.disks.s3.report', false],
    'non-Reverb broadcasting' => ['broadcasting.default', 'log'],
    'mock OCR' => ['ocr.driver', 'mock'],
    'non-delivering mailer' => ['mail.default', 'log'],
    'invalid sender address' => ['mail.from.address', 'invalid'],
    'missing trusted proxies' => ['production.trusted_proxies', null],
    'missing allowed hosts' => ['production.allowed_hosts', []],
]);

test('non-production environments may use local services', function (): void {
    $this->app['env'] = 'testing';

    config([
        'app.debug' => true,
        'app.url' => 'http://localhost',
        'filesystems.default' => 'local',
        'ocr.driver' => 'mock',
        'queue.default' => 'sync',
    ]);

    app(ProductionConfigurationValidator::class)->validate();

    expect(true)->toBeTrue();
});

test('composer package discovery can run before production configuration exists', function (): void {
    config(['app.url' => 'http://localhost']);
    $originalArguments = $_SERVER['argv'] ?? null;
    $_SERVER['argv'] = ['artisan', 'package:discover', '--ansi'];

    try {
        app(ProductionConfigurationValidator::class)->validate();
    } finally {
        if ($originalArguments === null) {
            unset($_SERVER['argv']);
        } else {
            $_SERVER['argv'] = $originalArguments;
        }
    }

    expect(true)->toBeTrue();
});
