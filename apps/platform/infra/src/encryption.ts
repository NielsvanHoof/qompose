import * as aws from "@pulumi/aws";
import { PlatformConfig } from "./config.js";

export interface OcrEncryption {
  encryptionKey: aws.kms.Key;
}

/**
 * KMS key used by the documents bucket, Textract SNS topic, and SQS queues.
 */
export function createOcrEncryption(config: PlatformConfig): OcrEncryption {
  const { environment } = config;
  const account = aws.getCallerIdentityOutput();

  const encryptionKey = new aws.kms.Key(environment.resourceName("ocr-key"), {
    description: `Qompose ${environment.name} OCR document encryption`,
    enableKeyRotation: true,
    deletionWindowInDays: environment.kmsDeletionWindowInDays,
    policy: account.accountId.apply((accountId) => JSON.stringify({
      Version: "2012-10-17",
      Statement: [{
        Sid: "EnableAccountPermissions",
        Effect: "Allow",
        Principal: { AWS: `arn:aws:iam::${accountId}:root` },
        Action: "kms:*",
        Resource: "*",
      }, {
        Sid: "AllowAwsServicesToPublishEncryptedMessages",
        Effect: "Allow",
        Principal: {
          Service: ["cloudwatch.amazonaws.com", "sns.amazonaws.com"],
        },
        Action: ["kms:Decrypt", "kms:GenerateDataKey"],
        Resource: "*",
        Condition: {
          StringEquals: {
            "aws:SourceAccount": accountId,
          },
        },
      }],
    })),
  });

  new aws.kms.Alias(environment.resourceName("ocr-key-alias"), {
    name: `alias/${environment.resourceName("ocr")}`,
    targetKeyId: encryptionKey.keyId,
  });

  return { encryptionKey };
}
