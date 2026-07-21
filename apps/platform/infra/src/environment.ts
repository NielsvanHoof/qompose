export type EnvironmentName = "staging" | "production";

export class DeploymentEnvironment {
  public readonly deletionProtection: boolean;
  public readonly webDesiredCount: number;
  public readonly workerDesiredCount: number;
  public readonly reverbDesiredCount: number;
  public readonly textractConsumerDesiredCount: number;
  public readonly databaseBackupRetentionDays: number;
  public readonly databaseInstanceClass: string;
  public readonly redisNodeType: string;

  public constructor(public readonly name: EnvironmentName) {
    const isProduction = name === "production";

    this.deletionProtection = isProduction;
    this.webDesiredCount = isProduction ? 2 : 1;
    this.workerDesiredCount = isProduction ? 2 : 1;
    this.reverbDesiredCount = isProduction ? 2 : 1;
    this.textractConsumerDesiredCount = isProduction ? 2 : 1;
    this.databaseBackupRetentionDays = isProduction ? 35 : 7;
    this.databaseInstanceClass = isProduction
      ? "db.t4g.small"
      : "db.t4g.micro";
    this.redisNodeType = isProduction
      ? "cache.t4g.small"
      : "cache.t4g.micro";
  }

  public resourceName(component: string): string {
    return `qompose-${this.name}-${component}`;
  }
}

export function deploymentEnvironmentFromStack(
  stackName: string,
): DeploymentEnvironment {
  if (stackName === "staging" || stackName === "production") {
    return new DeploymentEnvironment(stackName);
  }

  throw new Error(
    `Only staging and production stacks are supported, received "${stackName}".`,
  );
}
