import * as aws from "@pulumi/aws";
import { PlatformConfig } from "./config.js";
import { PlatformDataStores } from "./data.js";

export interface DocumentInfrastructure {
  bucket: aws.s3.BucketV2;
  textractTopic: aws.sns.Topic;
  resultsQueue: aws.sqs.Queue;
  resultsDeadLetterQueue: aws.sqs.Queue;
  textractPublishRole: aws.iam.Role;
}

export function createDocumentInfrastructure(
  config: PlatformConfig,
  dataStores: PlatformDataStores,
): DocumentInfrastructure {
  const { environment } = config;
  const bucket = new aws.s3.BucketV2(environment.resourceName("documents"), {
    bucketPrefix: `${environment.resourceName("documents")}-`,
    forceDestroy: environment.name === "staging",
  });
  new aws.s3.BucketPublicAccessBlock(environment.resourceName("documents-public-access"), {
    bucket: bucket.id,
    blockPublicAcls: true,
    blockPublicPolicy: true,
    ignorePublicAcls: true,
    restrictPublicBuckets: true,
  });
  new aws.s3.BucketOwnershipControls(environment.resourceName("documents-ownership"), {
    bucket: bucket.id,
    rule: { objectOwnership: "BucketOwnerEnforced" },
  });
  new aws.s3.BucketVersioningV2(environment.resourceName("documents-versioning"), {
    bucket: bucket.id,
    versioningConfiguration: { status: "Enabled" },
  });
  new aws.s3.BucketServerSideEncryptionConfigurationV2(
    environment.resourceName("documents-encryption"),
    {
      bucket: bucket.id,
      rules: [{
        applyServerSideEncryptionByDefault: {
          sseAlgorithm: "aws:kms",
          kmsMasterKeyId: dataStores.encryptionKey.arn,
        },
        bucketKeyEnabled: true,
      }],
    },
  );
  new aws.s3.BucketLifecycleConfigurationV2(
    environment.resourceName("documents-lifecycle"),
    {
      bucket: bucket.id,
      rules: [{
        id: "expire-noncurrent-versions",
        status: "Enabled",
        filter: {},
        noncurrentVersionExpiration: {
          noncurrentDays: environment.name === "production" ? 90 : 14,
        },
        abortIncompleteMultipartUpload: { daysAfterInitiation: 7 },
      }],
    },
  );

  const secureTransportPolicy = aws.iam.getPolicyDocumentOutput({
    statements: [{
      sid: "DenyInsecureTransport",
      effect: "Deny",
      principals: [{ type: "*", identifiers: ["*"] }],
      actions: ["s3:*"],
      resources: [bucket.arn, bucket.arn.apply((arn) => `${arn}/*`)],
      conditions: [{ test: "Bool", variable: "aws:SecureTransport", values: ["false"] }],
    }],
  });
  new aws.s3.BucketPolicy(environment.resourceName("documents-policy"), {
    bucket: bucket.id,
    policy: secureTransportPolicy.json,
  });

  const textractTopic = new aws.sns.Topic(environment.resourceName("textract-complete"), {
    name: environment.resourceName("textract-complete"),
    kmsMasterKeyId: dataStores.encryptionKey.arn,
  });
  const resultsDeadLetterQueue = new aws.sqs.Queue(
    environment.resourceName("textract-results-dlq"),
    {
      name: environment.resourceName("textract-results-dlq"),
      messageRetentionSeconds: 1_209_600,
      kmsMasterKeyId: dataStores.encryptionKey.arn,
    },
  );
  const resultsQueue = new aws.sqs.Queue(environment.resourceName("textract-results"), {
    name: environment.resourceName("textract-results"),
    visibilityTimeoutSeconds: 300,
    messageRetentionSeconds: 345_600,
    kmsMasterKeyId: dataStores.encryptionKey.arn,
    redrivePolicy: resultsDeadLetterQueue.arn.apply((deadLetterTargetArn) => JSON.stringify({
      deadLetterTargetArn,
      maxReceiveCount: 5,
    })),
  });
  const queuePolicy = aws.iam.getPolicyDocumentOutput({
    statements: [{
      sid: "AllowTextractTopic",
      effect: "Allow",
      principals: [{ type: "Service", identifiers: ["sns.amazonaws.com"] }],
      actions: ["sqs:SendMessage"],
      resources: [resultsQueue.arn],
      conditions: [{
        test: "ArnEquals",
        variable: "aws:SourceArn",
        values: [textractTopic.arn],
      }],
    }],
  });
  new aws.sqs.QueuePolicy(environment.resourceName("textract-results-policy"), {
    queueUrl: resultsQueue.id,
    policy: queuePolicy.json,
  });
  new aws.sns.TopicSubscription(environment.resourceName("textract-results-subscription"), {
    topic: textractTopic.arn,
    protocol: "sqs",
    endpoint: resultsQueue.arn,
    rawMessageDelivery: false,
  });

  const textractAssumeRolePolicy = aws.iam.getPolicyDocumentOutput({
    statements: [{
      effect: "Allow",
      principals: [{ type: "Service", identifiers: ["textract.amazonaws.com"] }],
      actions: ["sts:AssumeRole"],
    }],
  });
  const textractPublishRole = new aws.iam.Role(
    environment.resourceName("textract-publish-role"),
    { assumeRolePolicy: textractAssumeRolePolicy.json },
  );
  new aws.iam.RolePolicy(environment.resourceName("textract-publish-policy"), {
    role: textractPublishRole.id,
    policy: aws.iam.getPolicyDocumentOutput({
      statements: [{
        effect: "Allow",
        actions: ["sns:Publish"],
        resources: [textractTopic.arn],
      }, {
        effect: "Allow",
        actions: ["kms:Decrypt", "kms:GenerateDataKey"],
        resources: [dataStores.encryptionKey.arn],
      }],
    }).json,
  });

  return {
    bucket,
    textractTopic,
    resultsQueue,
    resultsDeadLetterQueue,
    textractPublishRole,
  };
}
