import assert from "node:assert/strict";
import test from "node:test";
import * as pulumi from "@pulumi/pulumi";

test("the OCR stack provisions documents plumbing and a local IAM user", async () => {
  const resources: Array<{ type: string; name: string; inputs: Record<string, unknown> }> = [];

  pulumi.runtime.setAllConfig({
    "aws:region": "eu-west-1",
  }, []);

  await pulumi.runtime.setMocks({
    call: (args) => {
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
        id: args.inputs.name ?? args.name,
        secret: "test-secret-access-key",
      };

      if (args.type === "aws:s3/bucketV2:BucketV2") {
        state.bucket = `${args.name}-bucket`;
      }

      if (args.type === "aws:sqs/queue:Queue") {
        state.url = `https://sqs.eu-west-1.amazonaws.com/123456789012/${args.name}`;
      }

      if (args.type === "aws:iam/accessKey:AccessKey") {
        state.id = "AKIATESTACCESSKEY1";
        state.secret = "test-secret-access-key";
      }

      resources.push({ type: args.type, name: args.name, inputs: args.inputs });

      return { id: `${args.name}-id`, state };
    },
  }, "qompose-platform", "production");

  await import("./index.js");
  await pulumi.runtime.disconnect();

  const resourceTypes = resources.map((resource) => resource.type);

  assert.equal(resourceTypes.includes("aws:ecs/cluster:Cluster"), false);
  assert.equal(resourceTypes.includes("aws:ecs/service:Service"), false);
  assert.equal(resourceTypes.includes("aws:rds/instance:Instance"), false);
  assert.equal(resourceTypes.includes("aws:ec2/vpc:Vpc"), false);

  assert.equal(resourceTypes.includes("aws:s3/bucketV2:BucketV2"), true);
  assert.equal(resourceTypes.includes("aws:sns/topic:Topic"), true);
  assert.equal(resourceTypes.filter((type) => type === "aws:sqs/queue:Queue").length, 2);
  assert.equal(resourceTypes.includes("aws:iam/user:User"), true);
  assert.equal(resourceTypes.includes("aws:iam/accessKey:AccessKey"), true);
  assert.equal(
    resources.some((resource) => resource.name.includes("textract-publish-role")),
    true,
  );
  assert.equal(
    resources.some((resource) => resource.name.includes("ocr-local-user")),
    true,
  );
});
