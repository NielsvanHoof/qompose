# Platform OCR infrastructure (Pulumi)

Provisions AWS resources for async Textract **AnalyzeDocument** (FORMS + TABLES) used by the Laravel platform app.

## Resources

- Private S3 documents bucket
- SNS topic for Textract job completion
- SQS queue (+ DLQ) subscribed to the SNS topic
- IAM role Textract assumes to publish to SNS
- IAM user + access key for local/dev app credentials

## Setup

**Important:** run Pulumi with an **admin/deployer** AWS identity (your personal IAM user or SSO profile), **not** the stack’s `qompose-platform-dev-app` access keys. The app user can use S3/Textract/SQS but cannot call `iam:PutUserPolicy` on itself — that is what causes `AccessDenied` on policy updates.

```bash
# Example: use your deployer profile for infra changes
export AWS_PROFILE=your-admin-profile   # or unset app keys and use ~/.aws/credentials [default]
unset AWS_ACCESS_KEY_ID AWS_SECRET_ACCESS_KEY AWS_SESSION_TOKEN

cd apps/platform/infra
npm install
pulumi stack select dev || pulumi stack init dev
pulumi up
```

After the stack is up, copy **app** outputs into `apps/platform/.env` (Laravel only — keep deployer creds for future `pulumi up`):

```bash
OCR_DRIVER=textract
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=$(pulumi stack output appAccessKeyId)
AWS_SECRET_ACCESS_KEY=$(pulumi stack output appSecretAccessKey --show-secrets)
AWS_DEFAULT_REGION=$(pulumi stack output awsRegion)
AWS_BUCKET=$(pulumi stack output documentsBucketName)
AWS_ENDPOINT=
AWS_URL=
AWS_USE_PATH_STYLE_ENDPOINT=false
TEXTRACT_SNS_TOPIC_ARN=$(pulumi stack output textractSnsTopicArn)
TEXTRACT_SNS_ROLE_ARN=$(pulumi stack output textractSnsRoleArn)
TEXTRACT_RESULTS_QUEUE_URL=$(pulumi stack output textractResultsQueueUrl)
```

Then run the app workers:

```bash
vendor/bin/sail artisan queue:work
vendor/bin/sail artisan textract:consume
```

Textract cannot read MinIO. Use the Pulumi S3 bucket (real AWS) for OCR demos.
