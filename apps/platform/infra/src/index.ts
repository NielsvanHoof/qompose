import { createOcrAlarms } from "./alarms.js";
import { loadPlatformConfig } from "./config.js";
import { createDocumentInfrastructure } from "./documents.js";
import { createOcrEncryption } from "./encryption.js";
import {
  createOcrOperatorAccess,
  exportOcrOperatorCredentials,
} from "./operator.js";

const config = loadPlatformConfig();
const encryption = createOcrEncryption(config);
const documents = createDocumentInfrastructure(config, encryption.encryptionKey);
const operator = createOcrOperatorAccess(config, documents, encryption.encryptionKey);
createOcrAlarms(config, documents);

const credentials = exportOcrOperatorCredentials(operator);

export const environment = config.environment.name;
export const documentsBucketName = documents.bucket.bucket;
export const textractSnsTopicArn = documents.textractTopic.arn;
export const textractPublishRoleArn = documents.textractPublishRole.arn;
export const textractResultsQueueUrl = documents.resultsQueue.url;
export const textractResultsDeadLetterQueueUrl = documents.resultsDeadLetterQueue.url;
export const ocrEncryptionKeyArn = encryption.encryptionKey.arn;
export const ocrLocalUserName = credentials.ocrLocalUserName;
export const ocrAccessKeyId = credentials.ocrAccessKeyId;
export const ocrSecretAccessKey = credentials.ocrSecretAccessKey;
