import * as pulumi from "@pulumi/pulumi";
import {
  DeploymentEnvironment,
  deploymentEnvironmentFromStack,
} from "./environment.js";

export interface PlatformConfig {
  environment: DeploymentEnvironment;
  domainName: string;
  certificateArn: string;
  hostedZoneId?: string;
  imageUri: string;
  mailFromAddress: string;
  alarmEmailAddress?: string;
  deployApplication: boolean;
  postgresEngineVersion: string;
  vpcCidr: string;
  publicSubnetCidrs: [string, string];
  privateSubnetCidrs: [string, string];
  appKey: pulumi.Output<string>;
  redisPassword: pulumi.Output<string>;
  reverbAppId: pulumi.Output<string>;
  reverbAppKey: pulumi.Output<string>;
  reverbAppSecret: pulumi.Output<string>;
}

export function loadPlatformConfig(): PlatformConfig {
  const config = new pulumi.Config();
  const environment = deploymentEnvironmentFromStack(pulumi.getStack());
  const cidrPrefix = environment.name === "production" ? "10.20" : "10.10";

  return {
    environment,
    domainName: config.require("domainName"),
    certificateArn: config.require("certificateArn"),
    hostedZoneId: config.get("hostedZoneId") ?? undefined,
    imageUri: config.require("imageUri"),
    mailFromAddress: config.require("mailFromAddress"),
    alarmEmailAddress: config.get("alarmEmailAddress") ?? undefined,
    deployApplication: config.getBoolean("deployApplication") ?? true,
    postgresEngineVersion: config.get("postgresEngineVersion") ?? "18",
    vpcCidr: `${cidrPrefix}.0.0/16`,
    publicSubnetCidrs: [`${cidrPrefix}.0.0/24`, `${cidrPrefix}.1.0/24`],
    privateSubnetCidrs: [`${cidrPrefix}.16.0/20`, `${cidrPrefix}.32.0/20`],
    appKey: config.requireSecret("appKey"),
    redisPassword: config.requireSecret("redisPassword"),
    reverbAppId: config.requireSecret("reverbAppId"),
    reverbAppKey: config.requireSecret("reverbAppKey"),
    reverbAppSecret: config.requireSecret("reverbAppSecret"),
  };
}
