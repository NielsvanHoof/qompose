<?php

declare(strict_types=1);

use App\Services\Ocr\Normalization\BedrockDocumentOverviewNormalizer;
use Aws\BedrockRuntime\BedrockRuntimeClient;
use Aws\Result;
use Tests\TestCase;

uses(TestCase::class);

test('bedrock document overview normalizer returns empty overview without fields or tables', function () {
    $bedrock = Mockery::mock(BedrockRuntimeClient::class);
    $bedrock->shouldNotReceive('converse');
    $this->app->instance(BedrockRuntimeClient::class, $bedrock);

    $overview = app(BedrockDocumentOverviewNormalizer::class)->describe([
        'document_type' => null,
        'summary' => null,
        'fields' => [],
        'tables' => [],
        'notes' => [],
        'confidence' => null,
    ]);

    expect($overview)->toBe([
        'document_type' => null,
        'summary' => null,
        'notes' => [],
    ]);
});

test('bedrock document overview normalizer parses converse json', function () {
    config([
        'ocr.bedrock.model_id' => 'eu.anthropic.claude-sonnet-4-20250514-v1:0',
        'ocr.bedrock.max_tokens' => 1024,
        'ocr.bedrock.temperature' => 0,
    ]);

    $bedrock = Mockery::mock(BedrockRuntimeClient::class);
    $bedrock->shouldReceive('converse')
        ->once()
        ->with(Mockery::on(function (array $args): bool {
            return ($args['modelId'] ?? null) === 'eu.anthropic.claude-sonnet-4-20250514-v1:0'
                && str_contains((string) data_get($args, 'messages.0.content.0.text'), 'BSN: 287505030')
                && ! array_key_exists('additionalModelRequestFields', $args);
        }))
        ->andReturn(new Result([
            'output' => [
                'message' => [
                    'content' => [
                        [
                            'text' => <<<'JSON'
```json
{
  "document_type": "payslip",
  "summary": "January payslip",
  "notes": ["Slight blur on footer"]
}
```
JSON
                        ],
                    ],
                ],
            ],
        ]));

    $this->app->instance(BedrockRuntimeClient::class, $bedrock);

    $overview = app(BedrockDocumentOverviewNormalizer::class)->describe([
        'document_type' => null,
        'summary' => null,
        'fields' => [
            [
                'label' => 'BSN',
                'value' => '287505030',
                'confidence' => 0.99,
                'sensitivity' => null,
            ],
        ],
        'tables' => [],
        'notes' => [],
        'confidence' => 0.99,
    ]);

    expect($overview)->toBe([
        'document_type' => 'payslip',
        'summary' => 'January payslip',
        'notes' => ['Slight blur on footer'],
    ]);
});

test('bedrock document overview normalizer rejects invalid json', function () {
    config(['ocr.bedrock.model_id' => 'eu.anthropic.claude-sonnet-4-20250514-v1:0']);

    $bedrock = Mockery::mock(BedrockRuntimeClient::class);
    $bedrock->shouldReceive('converse')
        ->once()
        ->andReturn(new Result([
            'output' => [
                'message' => [
                    'content' => [
                        ['text' => 'not-json'],
                    ],
                ],
            ],
        ]));

    $this->app->instance(BedrockRuntimeClient::class, $bedrock);

    expect(fn () => app(BedrockDocumentOverviewNormalizer::class)->describe([
        'document_type' => null,
        'summary' => null,
        'fields' => [
            ['label' => 'A', 'value' => '1', 'confidence' => null, 'sensitivity' => null],
        ],
        'tables' => [],
        'notes' => [],
        'confidence' => null,
    ]))->toThrow(RuntimeException::class, 'Bedrock returned invalid JSON.');
});
