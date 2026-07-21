<?php

declare(strict_types=1);

namespace App\Services\Production;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Application;
use LogicException;

use function is_array;
use function is_string;
use function parse_url;
use function sprintf;
use function str_starts_with;

final class ProductionConfigurationValidator
{
    public function __construct(
        private readonly Application $application,
        private readonly Repository $config,
    ) {}

    public function validate(): void
    {
        if (! $this->application->isProduction() || $this->isPackageDiscovery()) {
            return;
        }

        $invalidKeys = array_values(array_filter([
            $this->config->get('app.debug') === false ? null : 'app.debug',
            $this->isHttpsUrl() ? null : 'app.url',
            $this->matches('cache.default', 'redis'),
            $this->matches('queue.default', 'redis'),
            $this->matches('database.redis.default.scheme', 'tls'),
            $this->matches('database.redis.cache.scheme', 'tls'),
            $this->matches('session.driver', 'redis'),
            $this->config->get('session.encrypt') === true ? null : 'session.encrypt',
            $this->config->get('session.secure') === true ? null : 'session.secure',
            $this->hasSecureSessionDomain() ? null : 'session.domain',
            $this->matches('filesystems.default', 's3'),
            $this->config->get('filesystems.disks.s3.throw') === true ? null : 'filesystems.disks.s3.throw',
            $this->config->get('filesystems.disks.s3.report') === true ? null : 'filesystems.disks.s3.report',
            $this->matches('broadcasting.default', 'reverb'),
            $this->matches('ocr.driver', 'textract'),
            $this->hasDeliveringMailer() ? null : 'mail.default',
            $this->hasValidMailFromAddress() ? null : 'mail.from.address',
            $this->hasTrustedProxies() ? null : 'production.trusted_proxies',
            $this->hasAllowedHosts() ? null : 'production.allowed_hosts',
        ], static fn (?string $key): bool => $key !== null));

        if ($invalidKeys !== []) {
            throw new LogicException(sprintf(
                'Unsafe production configuration: %s.',
                implode(', ', $invalidKeys),
            ));
        }
    }

    private function matches(string $key, string $expected): ?string
    {
        return $this->config->get($key) === $expected ? null : $key;
    }

    private function isHttpsUrl(): bool
    {
        $url = $this->config->get('app.url');

        return is_string($url) && str_starts_with($url, 'https://');
    }

    private function hasDeliveringMailer(): bool
    {
        $mailer = $this->config->get('mail.default');

        return is_string($mailer) && ! in_array($mailer, ['array', 'log'], true);
    }

    private function hasValidMailFromAddress(): bool
    {
        $address = $this->config->get('mail.from.address');

        return is_string($address) && filter_var($address, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function hasSecureSessionDomain(): bool
    {
        $applicationHost = parse_url((string) $this->config->get('app.url'), PHP_URL_HOST);

        return is_string($applicationHost)
            && $applicationHost !== ''
            && $this->config->get('session.domain') === $applicationHost;
    }

    private function hasTrustedProxies(): bool
    {
        return $this->config->get('production.trusted_proxies') === 'REMOTE_ADDR';
    }

    private function hasAllowedHosts(): bool
    {
        $hosts = $this->config->get('production.allowed_hosts');

        return is_array($hosts) && $hosts !== [];
    }

    private function isPackageDiscovery(): bool
    {
        if (! $this->application->runningInConsole()) {
            return false;
        }

        $arguments = $_SERVER['argv'] ?? [];

        return in_array('package:discover', $arguments, true);
    }
}
