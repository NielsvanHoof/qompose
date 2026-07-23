import * as aws from "@pulumi/aws";
import * as pulumi from "@pulumi/pulumi";
import { PlatformConfig } from "./config.js";
import { DocumentInfrastructure } from "./documents.js";

export interface OcrOperatorAccess {
  user: aws.iam.User;
  accessKey: aws.iam.AccessKey;
}

/**
 * IAM user + access key for local/Sail OCR.
 * Root cannot assume roles — use these long-lived keys in .env instead.
 */
export function createOcrOperatorAccess(
  config: PlatformConfig,
  documents: DocumentInfrastructure,
  encryptionKey: aws.kms.Key,
): OcrOperatorAccess {
  const { environment } = config;

  const user = new aws.iam.User(environment.resourceName("ocr-local-user"), {
    name: environment.resourceName("ocr-local"),
    path: "/qompose/",
    tags: {
      Purpose: "Local Sail Textract + Bedrock OCR",
    },
  });

  const bucketObjects = documents.bucket.arn.apply((arn) => `${arn}/*`);

  new aws.iam.UserPolicy(environment.resourceName("ocr-local-policy"), {
    user: user.name,
    policy: aws.iam.getPolicyDocumentOutput({
      statements: [{
        actions: ["s3:ListBucket"],
        resources: [documents.bucket.arn],
      }, {
        actions: ["s3:GetObject", "s3:PutObject", "s3:DeleteObject"],
        resources: [bucketObjects],
      }, {
        actions: [
          "textract:StartDocumentAnalysis",
          "textract:GetDocumentAnalysis",
        ],
        resources: ["*"],
      }, {
        actions: ["bedrock:InvokeModel", "bedrock:InvokeModelWithResponseStream"],
        resources: [
          "arn:aws:bedrock:*::foundation-model/anthropic.*",
          "arn:aws:bedrock:*::foundation-model/openai.*",
          "arn:aws:bedrock:*:*:inference-profile/*",
        ],
      }, {
        actions: ["iam:PassRole"],
        resources: [documents.textractPublishRole.arn],
        conditions: [{
          test: "StringEquals",
          variable: "iam:PassedToService",
          values: ["textract.amazonaws.com"],
        }],
      }, {
        actions: [
          "sqs:ReceiveMessage",
          "sqs:DeleteMessage",
          "sqs:GetQueueAttributes",
          "sqs:ChangeMessageVisibility",
        ],
        resources: [documents.resultsQueue.arn],
      }, {
        actions: ["kms:Decrypt", "kms:Encrypt", "kms:GenerateDataKey"],
        resources: [encryptionKey.arn],
      }],
    }).json,
  });

  // Access key secret is stored in Pulumi state — sync into .env with the helper script.
  const accessKey = new aws.iam.AccessKey(environment.resourceName("ocr-local-access-key"), {
    user: user.name,
  });

  return { user, accessKey };
}

/** Secret stack outputs for local .env sync. */
export function exportOcrOperatorCredentials(access: OcrOperatorAccess): {
  ocrLocalUserName: pulumi.Output<string>;
  ocrAccessKeyId: pulumi.Output<string>;
  ocrSecretAccessKey: pulumi.Output<string>;
} {
  return {
    ocrLocalUserName: access.user.name,
    ocrAccessKeyId: access.accessKey.id,
    ocrSecretAccessKey: pulumi.secret(access.accessKey.secret),
  };
}
