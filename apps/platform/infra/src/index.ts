import * as aws from "@pulumi/aws";
import * as pulumi from "@pulumi/pulumi";

/**
 * Dev/local OCR stack: private documents bucket + Textract completion
 * via SNS → SQS. App IAM user credentials are exported for .env demos.
 */
const config = new pulumi.Config();
const namePrefix = config.get("namePrefix") ?? "qompose-platform-dev";
const region = aws.config.region ?? "eu-west-1";

const documentsBucket = new aws.s3.BucketV2(`${namePrefix}-documents`, {
  bucketPrefix: `${namePrefix}-docs-`,
  forceDestroy: true,
});

new aws.s3.BucketPublicAccessBlock(`${namePrefix}-documents-public-access`, {
  bucket: documentsBucket.id,
  blockPublicAcls: true,
  blockPublicPolicy: true,
  ignorePublicAcls: true,
  restrictPublicBuckets: true,
});

new aws.s3.BucketServerSideEncryptionConfigurationV2(
  `${namePrefix}-documents-sse`,
  {
    bucket: documentsBucket.id,
    rules: [
      {
        applyServerSideEncryptionByDefault: {
          sseAlgorithm: "AES256",
        },
      },
    ],
  },
);

const textractTopic = new aws.sns.Topic(`${namePrefix}-textract-complete`, {
  name: `${namePrefix}-textract-complete`,
});

const resultsDlq = new aws.sqs.Queue(`${namePrefix}-textract-results-dlq`, {
  name: `${namePrefix}-textract-results-dlq`,
  messageRetentionSeconds: 1_209_600,
});

const resultsQueue = new aws.sqs.Queue(`${namePrefix}-textract-results`, {
  name: `${namePrefix}-textract-results`,
  visibilityTimeoutSeconds: 60,
  messageRetentionSeconds: 345_600,
  redrivePolicy: pulumi
    .all([resultsDlq.arn])
    .apply(([dlqArn]) =>
      JSON.stringify({
        deadLetterTargetArn: dlqArn,
        maxReceiveCount: 5,
      }),
    ),
});

// Allow SNS to deliver Textract completion notifications into the results queue.
new aws.sqs.QueuePolicy(`${namePrefix}-textract-results-policy`, {
  queueUrl: resultsQueue.id,
  policy: pulumi
    .all([resultsQueue.arn, textractTopic.arn])
    .apply(([queueArn, topicArn]) =>
      JSON.stringify({
        Version: "2012-10-17",
        Statement: [
          {
            Sid: "AllowSnsPublish",
            Effect: "Allow",
            Principal: { Service: "sns.amazonaws.com" },
            Action: "sqs:SendMessage",
            Resource: queueArn,
            Condition: { ArnEquals: { "aws:SourceArn": topicArn } },
          },
        ],
      }),
    ),
});

new aws.sns.TopicSubscription(`${namePrefix}-textract-to-sqs`, {
  topic: textractTopic.arn,
  protocol: "sqs",
  endpoint: resultsQueue.arn,
  rawMessageDelivery: false,
});

// Textract publishes job status to SNS using this role.
const textractPublishRole = new aws.iam.Role(`${namePrefix}-textract-sns`, {
  name: `${namePrefix}-textract-sns`,
  assumeRolePolicy: JSON.stringify({
    Version: "2012-10-17",
    Statement: [
      {
        Effect: "Allow",
        Principal: { Service: "textract.amazonaws.com" },
        Action: "sts:AssumeRole",
      },
    ],
  }),
});

new aws.iam.RolePolicy(`${namePrefix}-textract-sns-publish`, {
  role: textractPublishRole.id,
  policy: textractTopic.arn.apply((topicArn) =>
    JSON.stringify({
      Version: "2012-10-17",
      Statement: [
        {
          Effect: "Allow",
          Action: "sns:Publish",
          Resource: topicArn,
        },
      ],
    }),
  ),
});

// Dev/local app credentials — prefer IRSA/instance roles in production.
const appUser = new aws.iam.User(`${namePrefix}-app`, {
  name: `${namePrefix}-app`,
});

const appAccessKey = new aws.iam.AccessKey(`${namePrefix}-app-key`, {
  user: appUser.name,
});

new aws.iam.UserPolicy(`${namePrefix}-app-policy`, {
  user: appUser.name,
  policy: pulumi
    .all([
      documentsBucket.arn,
      textractTopic.arn,
      resultsQueue.arn,
      textractPublishRole.arn,
    ])
    .apply(([bucketArn, topicArn, queueArn, snsRoleArn]) =>
      JSON.stringify({
        Version: "2012-10-17",
        Statement: [
          {
            Sid: "DocumentsBucket",
            Effect: "Allow",
            Action: [
              "s3:GetObject",
              "s3:PutObject",
              "s3:DeleteObject",
              "s3:ListBucket",
            ],
            Resource: [bucketArn, `${bucketArn}/*`],
          },
          {
            Sid: "TextractDocumentAnalysis",
            Effect: "Allow",
            Action: [
              "textract:StartDocumentAnalysis",
              "textract:GetDocumentAnalysis",
              "textract:StartDocumentTextDetection",
              "textract:GetDocumentTextDetection",
            ],
            Resource: "*",
          },
          {
            Sid: "PassTextractSnsRole",
            Effect: "Allow",
            Action: "iam:PassRole",
            Resource: snsRoleArn,
            Condition: {
              StringEquals: {
                "iam:PassedToService": "textract.amazonaws.com",
              },
            },
          },
          {
            Sid: "TextractResultsQueue",
            Effect: "Allow",
            Action: [
              "sqs:ReceiveMessage",
              "sqs:DeleteMessage",
              "sqs:GetQueueAttributes",
              "sqs:ChangeMessageVisibility",
            ],
            Resource: queueArn,
          },
          {
            Sid: "DescribeSnsTopic",
            Effect: "Allow",
            Action: ["sns:GetTopicAttributes"],
            Resource: topicArn,
          },
        ],
      }),
    ),
});

export const awsRegion = region;
export const documentsBucketName = documentsBucket.bucket;
export const textractSnsTopicArn = textractTopic.arn;
export const textractSnsRoleArn = textractPublishRole.arn;
export const textractResultsQueueUrl = resultsQueue.url;
export const textractResultsQueueArn = resultsQueue.arn;
export const appAccessKeyId = appAccessKey.id;
export const appSecretAccessKey = pulumi.secret(appAccessKey.secret);
