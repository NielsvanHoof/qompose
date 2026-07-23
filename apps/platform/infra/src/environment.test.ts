import assert from "node:assert/strict";
import test from "node:test";
import {
  DeploymentEnvironment,
  deploymentEnvironmentFromStack,
} from "./environment.js";

test("the production stack keeps deletion protection and long retention", () => {
  const environment = deploymentEnvironmentFromStack("production");

  assert.equal(environment.name, "production");
  assert.equal(environment.deletionProtection, true);
  assert.equal(environment.documentNoncurrentVersionDays, 90);
  assert.equal(environment.kmsDeletionWindowInDays, 30);
});

test("non-production stacks are rejected", () => {
  assert.throws(
    () => deploymentEnvironmentFromStack("staging"),
    /Only the production stack is supported/,
  );
  assert.throws(
    () => deploymentEnvironmentFromStack("developer"),
    /Only the production stack is supported/,
  );
});

test("resource names are stable and environment scoped", () => {
  const environment = new DeploymentEnvironment("production");

  assert.equal(environment.resourceName("documents"), "qompose-production-documents");
  assert.equal(environment.resourceName("ocr-operator"), "qompose-production-ocr-operator");
});
