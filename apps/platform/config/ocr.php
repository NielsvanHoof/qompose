<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | OCR Driver
    |--------------------------------------------------------------------------
    |
    | mock     — sync fake text (CI / offline). Completes inside the upload job.
    | textract — async AWS Textract StartDocumentTextDetection + SNS/SQS.
    |
    */

    'driver' => env('OCR_DRIVER', 'mock'),

    'textract' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
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

];
