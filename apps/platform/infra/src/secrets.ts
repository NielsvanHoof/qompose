import * as aws from "@pulumi/aws";
import * as pulumi from "@pulumi/pulumi";
import { PlatformConfig } from "./config.js";
import { PlatformDataStores } from "./data.js";

export interface ApplicationSecrets {
  secret: aws.secretsmanager.Secret;
  databaseSecretArn: pulumi.Output<string>;
}

export function createApplicationSecrets(
  config: PlatformConfig,
  dataStores: PlatformDataStores,
): ApplicationSecrets {
  const { environment } = config;
  const secret = new aws.secretsmanager.Secret(environment.resourceName("application-secrets"), {
    namePrefix: `${environment.resourceName("application")}-`,
    kmsKeyId: dataStores.encryptionKey.arn,
    recoveryWindowInDays: environment.deletionProtection ? 30 : 7,
  });

  new aws.secretsmanager.SecretVersion(environment.resourceName("application-secret-values"), {
    secretId: secret.id,
    secretString: pulumi.secret(pulumi.all([
      config.appKey,
      config.redisPassword,
      config.reverbAppId,
      config.reverbAppKey,
      config.reverbAppSecret,
    ]).apply(([appKey, redisPassword, reverbAppId, reverbAppKey, reverbAppSecret]) => JSON.stringify({
      APP_KEY: appKey,
      REDIS_PASSWORD: redisPassword,
      REVERB_APP_ID: reverbAppId,
      REVERB_APP_KEY: reverbAppKey,
      REVERB_APP_SECRET: reverbAppSecret,
    }))),
  });

  const databaseSecretArn = dataStores.database.masterUserSecrets.apply((secrets) => {
    const managedSecret = secrets[0];

    if (!managedSecret) {
      throw new Error("RDS did not create a managed master-user secret.");
    }

    return managedSecret.secretArn;
  });

  return { secret, databaseSecretArn };
}
