import assert from "node:assert/strict";
import test from "node:test";
import * as pulumi from "@pulumi/pulumi";

test("the stack provisions production services without long-lived IAM users", async () => {
  const resources: Array<{ type: string; name: string; inputs: Record<string, unknown> }> = [];

  pulumi.runtime.setAllConfig({
    "qompose-platform:domainName": "staging.example.com",
    "qompose-platform:certificateArn": "arn:aws:acm:eu-west-1:123456789012:certificate/test",
    "qompose-platform:imageUri": "123456789012.dkr.ecr.eu-west-1.amazonaws.com/qompose:test",
    "qompose-platform:mailFromAddress": "support@example.com",
    "qompose-platform:deployApplication": "true",
    "qompose-platform:appKey": "base64:test",
    "qompose-platform:redisPassword": "a-production-grade-redis-password",
    "qompose-platform:reverbAppId": "test-app",
    "qompose-platform:reverbAppKey": "test-key",
    "qompose-platform:reverbAppSecret": "test-secret",
    "aws:region": "eu-west-1",
  }, [
    "qompose-platform:appKey",
    "qompose-platform:redisPassword",
    "qompose-platform:reverbAppId",
    "qompose-platform:reverbAppKey",
    "qompose-platform:reverbAppSecret",
  ]);

  await pulumi.runtime.setMocks({
    call: (args) => {
      if (args.token.includes("getAvailabilityZones")) {
        return { names: ["eu-west-1a", "eu-west-1b", "eu-west-1c"] };
      }

      if (args.token.includes("getCallerIdentity")) {
        return { accountId: "123456789012", arn: "arn:aws:iam::123456789012:root", userId: "root" };
      }

      if (args.token.includes("getPolicyDocument")) {
        return { json: JSON.stringify({ Version: "2012-10-17", Statement: [] }) };
      }

      return args.inputs;
    },
    newResource: (args) => {
      const state: Record<string, unknown> = {
        ...args.inputs,
        arn: `arn:aws:test:eu-west-1:123456789012:${args.name}`,
        name: args.inputs.name ?? args.name,
      };

      if (args.type === "aws:rds/instance:Instance") {
        state.address = "database.internal";
        state.masterUserSecrets = [{ secretArn: "arn:aws:secretsmanager:eu-west-1:123456789012:secret:database" }];
      }

      if (args.type === "aws:elasticache/replicationGroup:ReplicationGroup") {
        state.primaryEndpointAddress = "redis.internal";
        state.replicationGroupId = args.inputs.replicationGroupId;
      }

      if (args.type === "aws:s3/bucketV2:BucketV2") {
        state.bucket = `${args.name}-bucket`;
      }

      if (args.type === "aws:sqs/queue:Queue") {
        state.url = `https://sqs.eu-west-1.amazonaws.com/123456789012/${args.name}`;
      }

      if (args.type === "aws:lb/loadBalancer:LoadBalancer") {
        state.arnSuffix = `app/${args.name}/test`;
        state.dnsName = `${args.name}.elb.amazonaws.com`;
        state.zoneId = "Z123";
      }

      if (args.type === "aws:ecr/repository:Repository") {
        state.repositoryUrl = `123456789012.dkr.ecr.eu-west-1.amazonaws.com/${args.name}`;
      }

      resources.push({ type: args.type, name: args.name, inputs: args.inputs });

      return { id: `${args.name}-id`, state };
    },
  }, "qompose-platform", "staging");

  await import("./index.js");
  await pulumi.runtime.disconnect();

  const resourceTypes = resources.map((resource) => resource.type);

  assert.equal(resourceTypes.includes("aws:iam/user:User"), false);
  assert.equal(resourceTypes.includes("aws:iam/accessKey:AccessKey"), false);
  assert.equal(resourceTypes.includes("aws:rds/instance:Instance"), true);
  assert.equal(resourceTypes.includes("aws:elasticache/replicationGroup:ReplicationGroup"), true);
  assert.equal(resourceTypes.includes("aws:ecs/cluster:Cluster"), true);
  assert.equal(resourceTypes.filter((type) => type === "aws:ecs/service:Service").length, 5);
  assert.equal(resourceTypes.filter((type) => type === "aws:appautoscaling/target:Target").length, 4);
  assert.equal(resourceTypes.filter((type) => type === "aws:cloudwatch/metricAlarm:MetricAlarm").length, 5);
});
