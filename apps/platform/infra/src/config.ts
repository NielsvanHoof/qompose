import * as pulumi from "@pulumi/pulumi";
import {
  DeploymentEnvironment,
  deploymentEnvironmentFromStack,
} from "./environment.js";

export interface PlatformConfig {
  environment: DeploymentEnvironment;
  /** Optional email for Textract results DLQ CloudWatch alarms. */
  alarmEmailAddress?: string;
}

export function loadPlatformConfig(): PlatformConfig {
  const config = new pulumi.Config();
  const environment = deploymentEnvironmentFromStack(pulumi.getStack());

  return {
    environment,
    alarmEmailAddress: config.get("alarmEmailAddress") ?? undefined,
  };
}
