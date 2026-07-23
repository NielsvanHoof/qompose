import * as aws from "@pulumi/aws";
import { PlatformConfig } from "./config.js";
import { DocumentInfrastructure } from "./documents.js";

/**
 * Optional alarm when the Textract results DLQ receives messages.
 */
export function createOcrAlarms(
  config: PlatformConfig,
  documents: DocumentInfrastructure,
): void {
  const { environment, alarmEmailAddress } = config;

  if (!alarmEmailAddress) {
    return;
  }

  const topic = new aws.sns.Topic(environment.resourceName("ocr-alarms"), {
    name: environment.resourceName("ocr-alarms"),
  });
  new aws.sns.TopicSubscription(environment.resourceName("ocr-alarms-email"), {
    topic: topic.arn,
    protocol: "email",
    endpoint: alarmEmailAddress,
  });

  new aws.cloudwatch.MetricAlarm(environment.resourceName("textract-dlq-alarm"), {
    name: environment.resourceName("textract-dlq-alarm"),
    alarmDescription: "Textract OCR results landed on the dead-letter queue",
    namespace: "AWS/SQS",
    metricName: "ApproximateNumberOfMessagesVisible",
    dimensions: { QueueName: documents.resultsDeadLetterQueue.name },
    statistic: "Maximum",
    period: 300,
    evaluationPeriods: 1,
    threshold: 1,
    comparisonOperator: "GreaterThanOrEqualToThreshold",
    treatMissingData: "notBreaching",
    alarmActions: [topic.arn],
  });
}
