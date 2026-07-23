<?php

declare(strict_types=1);

use App\Services\Ocr\Normalization\BedrockDocumentStructureNormalizer;
use Aws\BedrockRuntime\BedrockRuntimeClient;
use Aws\Result;
use Tests\TestCase;

uses(TestCase::class);

test('bedrock document structure normalizer returns empty payload for blank text', function () {
    $bedrock = Mockery::mock(BedrockRuntimeClient::class);
    $bedrock->shouldNotReceive('converse');
    $this->app->instance(BedrockRuntimeClient::class, $bedrock);

    $payload = app(BedrockDocumentStructureNormalizer::class)->structure('   ');

    expect($payload)->toBe([
        'document_type' => null,
        'summary' => null,
        'fields' => [],
        'tables' => [],
        'notes' => [],
    ]);
});

test('bedrock document structure normalizer parses converse json and normalizes shape', function () {
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
                && str_contains((string) data_get($args, 'messages.0.content.0.text'), 'BSN 287505030')
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
  "fields": [
    {"label": "BSN", "value": "287505030"},
    {"label": "Period", "value": ["2026-01", "January"]}
  ],
  "tables": [
    {
      "title": "Earnings",
      "headers": ["Code", "Amount"],
      "rows": [["1000", "2500"]]
    },
    {"title": null, "headers": [], "rows": []}
  ],
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

    $payload = app(BedrockDocumentStructureNormalizer::class)->structure("BSN 287505030\nPeriod January");

    expect($payload['document_type'])->toBe('payslip')
        ->and($payload['summary'])->toBe('January payslip')
        ->and($payload['fields'])->toBe([
            ['label' => 'BSN', 'value' => '287505030'],
            ['label' => 'Period', 'value' => ['2026-01', 'January']],
        ])
        ->and($payload['tables'])->toHaveCount(1)
        ->and($payload['tables'][0]['title'])->toBe('Earnings')
        ->and($payload['notes'])->toBe(['Slight blur on footer']);
});

test('bedrock document structure normalizer skips gpt-oss reasoning blocks and uses text', function () {
    config([
        'ocr.bedrock.model_id' => 'openai.gpt-oss-20b-1:0',
        'ocr.bedrock.max_tokens' => 2048,
        'ocr.bedrock.temperature' => 0,
    ]);

    $bedrock = Mockery::mock(BedrockRuntimeClient::class);
    $bedrock->shouldReceive('converse')
        ->once()
        ->with(Mockery::on(function (array $args): bool {
            return ($args['modelId'] ?? null) === 'openai.gpt-oss-20b-1:0'
                && ($args['additionalModelRequestFields']['reasoning_effort'] ?? null) === 'low'
                && ($args['inferenceConfig']['maxTokens'] ?? null) >= 1024;
        }))
        ->andReturn(new Result([
            'stopReason' => 'end_turn',
            'output' => [
                'message' => [
                    'content' => [
                        [
                            'reasoningContent' => [
                                'reasoningText' => [
                                    'text' => 'I will emit JSON only.',
                                ],
                            ],
                        ],
                        [
                            'text' => '{"document_type":"payslip","summary":null,"fields":[{"label":"BSN","value":"1"}],"tables":[],"notes":[]}',
                        ],
                    ],
                ],
            ],
        ]));

    $this->app->instance(BedrockRuntimeClient::class, $bedrock);

    $payload = app(BedrockDocumentStructureNormalizer::class)->structure('BSN 1');

    expect($payload['document_type'])->toBe('payslip')
        ->and($payload['fields'][0])->toBe(['label' => 'BSN', 'value' => '1']);
});

test('bedrock document structure normalizer explains when only reasoning was returned', function () {
    config(['ocr.bedrock.model_id' => 'openai.gpt-oss-20b-1:0']);

    $bedrock = Mockery::mock(BedrockRuntimeClient::class);
    $bedrock->shouldReceive('converse')
        ->once()
        ->andReturn(new Result([
            'stopReason' => 'max_tokens',
            'output' => [
                'message' => [
                    'content' => [
                        [
                            'reasoningContent' => [
                                'reasoningText' => ['text' => 'Still thinking…'],
                            ],
                        ],
                    ],
                ],
            ],
        ]));

    $this->app->instance(BedrockRuntimeClient::class, $bedrock);

    expect(fn () => app(BedrockDocumentStructureNormalizer::class)->structure('BSN 1'))
        ->toThrow(RuntimeException::class, 'Bedrock returned reasoning but no answer text');
});

test('bedrock document structure normalizer rejects invalid json', function () {
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

    expect(fn () => app(BedrockDocumentStructureNormalizer::class)->structure('BSN 1'))
        ->toThrow(RuntimeException::class, 'Bedrock returned invalid JSON.');
});
