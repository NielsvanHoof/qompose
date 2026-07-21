import assert from "node:assert/strict";
import test from "node:test";
import {
  DeploymentEnvironment,
  deploymentEnvironmentFromStack,
} from "./environment.js";

test("the staging stack uses staging safety and capacity defaults", () => {
  const environment = deploymentEnvironmentFromStack("staging");

  assert.equal(environment.name, "staging");
  assert.equal(environment.deletionProtection, false);
  assert.equal(environment.webDesiredCount, 1);
  assert.equal(environment.databaseBackupRetentionDays, 7);
});

test("the production stack enables deletion protection and high availability", () => {
  const environment = deploymentEnvironmentFromStack("production");

  assert.equal(environment.name, "production");
  assert.equal(environment.deletionProtection, true);
  assert.equal(environment.webDesiredCount, 2);
  assert.equal(environment.databaseBackupRetentionDays, 35);
});

test("unknown stacks cannot silently receive production resources", () => {
  assert.throws(
    () => deploymentEnvironmentFromStack("developer"),
    /Only staging and production stacks are supported/,
  );
});

test("resource names are stable and environment scoped", () => {
  const environment = new DeploymentEnvironment("production");

  assert.equal(environment.resourceName("web"), "qompose-production-web");
});
