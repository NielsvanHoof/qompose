<?php

declare(strict_types=1);

use App\Services\Ocr\Configuration\OcrConfigurationValidator;

beforeEach(function () {
    config([
        'ocr.driver' => 'textract',
        'ocr.textract.key' => null,
        'ocr.textract.secret' => null,
        'ocr.textract.region' => 'eu-west-1',
        'ocr.textract.bucket' => 'production-documents',
        'ocr.textract.sns_topic_arn' => 'arn:aws:sns:eu-west-1:123456789012:textract-results',
        'ocr.textract.sns_role_arn' => 'arn:aws:iam::123456789012:role/textract-sns',
        'ocr.textract.results_queue_url' => 'https://sqs.eu-west-1.amazonaws.com/123456789012/textract-results',
        'ocr.textract.sqs_wait_time_seconds' => 20,
        'ocr.textract.sqs_max_messages' => 5,
        'ocr.textract.sqs_max_receive_count' => 5,
        'ocr.bedrock.model_id' => 'eu.anthropic.claude-sonnet-4-20250514-v1:0',
        'ocr.bedrock.region' => 'eu-west-1',
        'ocr.bedrock.max_tokens' => 4096,
    ]);
});

test('valid production textract configuration passes without static credentials', function () {
    $this->app->instance('env', 'production');

    app(OcrConfigurationValidator::class)->validate();

    expect(true)->toBeTrue();
});

test('validation is skipped outside production', function () {
    config(['ocr.driver' => 'unsupported']);

    app(OcrConfigurationValidator::class)->validate();

    expect(true)->toBeTrue();
});

test('validation is skipped during package discovery even in production', function () {
    $this->app->instance('env', 'production');
    config(['ocr.driver' => 'mock']);

    $originalArgv = $_SERVER['argv'] ?? null;
    $_SERVER['argv'] = ['artisan', 'package:discover', '--ansi'];

    try {
        app(OcrConfigurationValidator::class)->validate();
    } finally {
        if ($originalArgv === null) {
            unset($_SERVER['argv']);
        } else {
            $_SERVER['argv'] = $originalArgv;
        }
    }

    expect(true)->toBeTrue();
});

test('production requires the textract driver', function (mixed $driver) {
    $this->app->instance('env', 'production');
    config(['ocr.driver' => $driver]);

    expect(fn () => app(OcrConfigurationValidator::class)->validate())
        ->toThrow(LogicException::class, 'Production OCR requires OCR_DRIVER=textract.');
})->with([
    'mock' => 'mock',
    'unsupported' => 'unsupported',
    'missing' => null,
]);

test('production rejects invalid textract settings', function (string $key, mixed $value) {
    $this->app->instance('env', 'production');
    config([$key => $value]);

    expect(fn () => app(OcrConfigurationValidator::class)->validate())
        ->toThrow(LogicException::class, $key);
})->with([
    'missing bucket' => ['ocr.textract.bucket', null],
    'invalid region' => ['ocr.textract.region', 'invalid'],
    'invalid SNS topic ARN' => ['ocr.textract.sns_topic_arn', 'not-an-arn'],
    'invalid SNS role ARN' => ['ocr.textract.sns_role_arn', 'not-an-arn'],
    'insecure results queue URL' => ['ocr.textract.results_queue_url', 'http://example.com/results'],
    'wait time below range' => ['ocr.textract.sqs_wait_time_seconds', -1],
    'wait time above range' => ['ocr.textract.sqs_wait_time_seconds', 21],
    'max messages below range' => ['ocr.textract.sqs_max_messages', 0],
    'max messages above range' => ['ocr.textract.sqs_max_messages', 11],
    'max receive count below range' => ['ocr.textract.sqs_max_receive_count', 0],
    'missing bedrock model' => ['ocr.bedrock.model_id', null],
    'invalid bedrock region' => ['ocr.bedrock.region', 'invalid'],
    'bedrock max tokens too low' => ['ocr.bedrock.max_tokens', 128],
]);

test('production requires static AWS credentials to be configured as a pair', function (string $key, mixed $value) {
    $this->app->instance('env', 'production');
    config([$key => $value]);

    expect(fn () => app(OcrConfigurationValidator::class)->validate())
        ->toThrow(LogicException::class, 'ocr.textract.key/secret');
})->with([
    'key only' => ['ocr.textract.key', 'access-key'],
    'secret only' => ['ocr.textract.secret', 'secret-key'],
]);
