<?php

declare(strict_types=1);

namespace App\Services\Ocr\Configuration;

use const FILTER_VALIDATE_URL;

use App\Enums\OcrDriver;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Application;
use LogicException;

use function filter_var;
use function in_array;
use function is_int;
use function is_string;
use function mb_trim;
use function preg_match;
use function sprintf;

final class OcrConfigurationValidator
{
    public function __construct(
        private readonly Application $application,
        private readonly Repository $config,
    ) {}

    public function validate(): void
    {
        // Composer runs `package:discover` before .env exists. Laravel then
        // defaults APP_ENV to production, which must not fail the install.
        if ($this->isPackageDiscovery()) {
            return;
        }

        if (! $this->application->isProduction()) {
            return;
        }

        $configuredDriver = $this->config->get('ocr.driver');
        $driver = is_string($configuredDriver)
            ? OcrDriver::tryFrom($configuredDriver)
            : null;

        if ($driver !== OcrDriver::Textract) {
            throw new LogicException('Production OCR requires OCR_DRIVER=textract.');
        }

        $invalidKeys = $this->invalidTextractConfigurationKeys();

        if ($invalidKeys !== []) {
            throw new LogicException(sprintf(
                'Invalid production Textract configuration: %s.',
                implode(', ', $invalidKeys),
            ));
        }
    }

    /**
     * True while Composer triggers `php artisan package:discover`.
     */
    private function isPackageDiscovery(): bool
    {
        if (! $this->application->runningInConsole()) {
            return false;
        }

        $argv = $_SERVER['argv'] ?? [];

        return in_array('package:discover', $argv, true);
    }

    /**
     * @return list<string>
     */
    private function invalidTextractConfigurationKeys(): array
    {
        $invalidKeys = [];

        if (! $this->isConfiguredString('ocr.textract.bucket')) {
            $invalidKeys[] = 'ocr.textract.bucket';
        }

        if (! $this->isValidRegion($this->config->get('ocr.textract.region'))) {
            $invalidKeys[] = 'ocr.textract.region';
        }

        if (! $this->isArnForService($this->config->get('ocr.textract.sns_topic_arn'), 'sns')) {
            $invalidKeys[] = 'ocr.textract.sns_topic_arn';
        }

        if (! $this->isArnForService($this->config->get('ocr.textract.sns_role_arn'), 'iam')) {
            $invalidKeys[] = 'ocr.textract.sns_role_arn';
        }

        if (! $this->isSecureUrl($this->config->get('ocr.textract.results_queue_url'))) {
            $invalidKeys[] = 'ocr.textract.results_queue_url';
        }

        $waitTime = $this->config->get('ocr.textract.sqs_wait_time_seconds');

        if (! is_int($waitTime) || $waitTime < 0 || $waitTime > 20) {
            $invalidKeys[] = 'ocr.textract.sqs_wait_time_seconds';
        }

        $maxMessages = $this->config->get('ocr.textract.sqs_max_messages');

        if (! is_int($maxMessages) || $maxMessages < 1 || $maxMessages > 10) {
            $invalidKeys[] = 'ocr.textract.sqs_max_messages';
        }

        if (! $this->isConfiguredString('ocr.bedrock.model_id')) {
            $invalidKeys[] = 'ocr.bedrock.model_id';
        }

        if (! $this->isValidRegion($this->config->get('ocr.bedrock.region'))) {
            $invalidKeys[] = 'ocr.bedrock.region';
        }

        $maxTokens = $this->config->get('ocr.bedrock.max_tokens');

        if (! is_int($maxTokens) || $maxTokens < 256) {
            $invalidKeys[] = 'ocr.bedrock.max_tokens';
        }

        $hasKey = $this->isConfiguredString('ocr.textract.key');
        $hasSecret = $this->isConfiguredString('ocr.textract.secret');

        if ($hasKey !== $hasSecret) {
            $invalidKeys[] = 'ocr.textract.key/secret';
        }

        return $invalidKeys;
    }

    private function isConfiguredString(string $key): bool
    {
        $value = $this->config->get($key);

        return is_string($value) && mb_trim($value) !== '';
    }

    private function isValidRegion(mixed $value): bool
    {
        return is_string($value)
            && preg_match('/^[a-z0-9-]+-\d+$/', $value) === 1;
    }

    private function isArnForService(mixed $value, string $service): bool
    {
        return is_string($value)
            && preg_match('/^arn:[^:]+:'.$service.':/', $value) === 1;
    }

    private function isSecureUrl(mixed $value): bool
    {
        return is_string($value)
            && filter_var($value, FILTER_VALIDATE_URL) !== false
            && str_starts_with($value, 'https://');
    }
}
