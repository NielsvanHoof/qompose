export type EnvironmentName = "production";

/**
 * Production-only naming and retention defaults for the OCR stack.
 */
export class DeploymentEnvironment {
  public readonly deletionProtection = true;
  public readonly documentNoncurrentVersionDays = 90;
  public readonly kmsDeletionWindowInDays = 30;

  public constructor(public readonly name: EnvironmentName) {}

  public resourceName(component: string): string {
    return `qompose-${this.name}-${component}`;
  }
}

export function deploymentEnvironmentFromStack(
  stackName: string,
): DeploymentEnvironment {
  if (stackName === "production") {
    return new DeploymentEnvironment(stackName);
  }

  throw new Error(
    `Only the production stack is supported, received "${stackName}".`,
  );
}
