<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | OCR Driver
    |--------------------------------------------------------------------------
    |
    | mock     — sync fake text (CI / offline). Completes inside the upload job.
    | textract — async AWS Textract StartDocumentTextDetection + SNS/SQS,
    |            then Bedrock structures the LINE text into JSON.
    |
    */

    'driver' => env('OCR_DRIVER', 'mock'),

    'textract' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        // Set when using temporary credentials from sts:AssumeRole (ocr operator).
        'token' => env('AWS_SESSION_TOKEN'),
        'region' => env('AWS_DEFAULT_REGION', 'eu-west-1'),
        'bucket' => env('AWS_BUCKET'),
        // SNS topic Textract publishes completion events to.
        'sns_topic_arn' => env('TEXTRACT_SNS_TOPIC_ARN'),
        // IAM role Textract assumes to publish to SNS.
        'sns_role_arn' => env('TEXTRACT_SNS_ROLE_ARN'),
        // SQS queue subscribed to the SNS topic (SNS envelope, not Laravel jobs).
        'results_queue_url' => env('TEXTRACT_RESULTS_QUEUE_URL'),
        // Long-poll wait for textract:consume (seconds, max 20).
        'sqs_wait_time_seconds' => (int) env('TEXTRACT_SQS_WAIT_TIME', 20),
        'sqs_max_messages' => (int) env('TEXTRACT_SQS_MAX_MESSAGES', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Bedrock structuring (Textract driver only)
    |--------------------------------------------------------------------------
    |
    | After DetectDocumentText finishes, we send LINE text to Bedrock so Claude
    | can group fields/tables into our extraction JSON schema.
    | Prefer an EU inference profile ID in eu-west-1 (not the bare model ID).
    |
    */

    'bedrock' => [
        'model_id' => env(
            'OCR_BEDROCK_MODEL_ID',
            'eu.anthropic.claude-sonnet-4-20250514-v1:0',
        ),
        'region' => env('OCR_BEDROCK_REGION', env('AWS_DEFAULT_REGION', 'eu-west-1')),
        'max_tokens' => (int) env('OCR_BEDROCK_MAX_TOKENS', 8192),
        // Keep temperature low — we want deterministic structured JSON.
        'temperature' => (float) env('OCR_BEDROCK_TEMPERATURE', 0),
    ],

];
