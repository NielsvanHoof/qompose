import * as aws from "@pulumi/aws";
import * as pulumi from "@pulumi/pulumi";
import { PlatformConfig } from "./config.js";
import { PlatformNetwork } from "./network.js";

export interface PlatformDataStores {
  database: aws.rds.Instance;
  redis: aws.elasticache.ReplicationGroup;
  encryptionKey: aws.kms.Key;
}

export function createDataStores(
  config: PlatformConfig,
  network: PlatformNetwork,
): PlatformDataStores {
  const { environment } = config;
  const account = aws.getCallerIdentityOutput();
  const encryptionKey = new aws.kms.Key(environment.resourceName("data-key"), {
    description: `Qompose ${environment.name} application data`,
    enableKeyRotation: true,
    deletionWindowInDays: environment.deletionProtection ? 30 : 7,
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
  new aws.kms.Alias(environment.resourceName("data-key-alias"), {
    name: `alias/${environment.resourceName("data")}`,
    targetKeyId: encryptionKey.keyId,
  });

  const databaseSubnetGroup = new aws.rds.SubnetGroup(
    environment.resourceName("database-subnets"),
    { subnetIds: network.privateSubnets.map((subnet) => subnet.id) },
  );
  const database = new aws.rds.Instance(environment.resourceName("database"), {
    identifier: environment.resourceName("database"),
    engine: "postgres",
    engineVersion: config.postgresEngineVersion,
    instanceClass: environment.databaseInstanceClass,
    dbName: "qompose",
    username: "qompose",
    manageMasterUserPassword: true,
    masterUserSecretKmsKeyId: encryptionKey.arn,
    allocatedStorage: environment.name === "production" ? 50 : 20,
    maxAllocatedStorage: environment.name === "production" ? 500 : 100,
    storageType: "gp3",
    storageEncrypted: true,
    kmsKeyId: encryptionKey.arn,
    dbSubnetGroupName: databaseSubnetGroup.name,
    vpcSecurityGroupIds: [network.databaseSecurityGroup.id],
    publiclyAccessible: false,
    multiAz: environment.name === "production",
    backupRetentionPeriod: environment.databaseBackupRetentionDays,
    backupWindow: "01:00-02:00",
    maintenanceWindow: "sun:03:00-sun:04:00",
    autoMinorVersionUpgrade: true,
    deletionProtection: environment.deletionProtection,
    skipFinalSnapshot: false,
    finalSnapshotIdentifier: pulumi.interpolate`${environment.resourceName("database")}-final`,
    copyTagsToSnapshot: true,
    performanceInsightsEnabled: true,
    performanceInsightsKmsKeyId: encryptionKey.arn,
    enabledCloudwatchLogsExports: ["postgresql", "upgrade"],
    applyImmediately: environment.name === "staging",
  });

  const redisSubnetGroup = new aws.elasticache.SubnetGroup(
    environment.resourceName("redis-subnets"),
    { subnetIds: network.privateSubnets.map((subnet) => subnet.id) },
  );
  const redis = new aws.elasticache.ReplicationGroup(
    environment.resourceName("redis"),
    {
      replicationGroupId: environment.resourceName("redis"),
      description: `Qompose ${environment.name} cache, session, and queue store`,
      engine: "redis",
      engineVersion: "7.1",
      nodeType: environment.redisNodeType,
      numCacheClusters: environment.name === "production" ? 2 : 1,
      automaticFailoverEnabled: environment.name === "production",
      multiAzEnabled: environment.name === "production",
      subnetGroupName: redisSubnetGroup.name,
      securityGroupIds: [network.redisSecurityGroup.id],
      atRestEncryptionEnabled: true,
      transitEncryptionEnabled: true,
      authToken: config.redisPassword,
      authTokenUpdateStrategy: "SET",
      kmsKeyId: encryptionKey.arn,
      snapshotRetentionLimit: environment.databaseBackupRetentionDays,
      snapshotWindow: "02:00-03:00",
      maintenanceWindow: "sun:04:00-sun:05:00",
      applyImmediately: environment.name === "staging",
    },
  );

  return { database, redis, encryptionKey };
}
