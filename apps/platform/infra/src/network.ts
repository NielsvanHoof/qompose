import * as aws from "@pulumi/aws";
import { PlatformConfig } from "./config.js";

export interface PlatformNetwork {
  vpc: aws.ec2.Vpc;
  publicSubnets: [aws.ec2.Subnet, aws.ec2.Subnet];
  privateSubnets: [aws.ec2.Subnet, aws.ec2.Subnet];
  loadBalancerSecurityGroup: aws.ec2.SecurityGroup;
  applicationSecurityGroup: aws.ec2.SecurityGroup;
  databaseSecurityGroup: aws.ec2.SecurityGroup;
  redisSecurityGroup: aws.ec2.SecurityGroup;
}

export function createNetwork(config: PlatformConfig): PlatformNetwork {
  const { environment } = config;
  const availabilityZones = aws.getAvailabilityZonesOutput({ state: "available" });
  const vpc = new aws.ec2.Vpc(environment.resourceName("vpc"), {
    cidrBlock: config.vpcCidr,
    enableDnsHostnames: true,
    enableDnsSupport: true,
    tags: { Name: environment.resourceName("vpc") },
  });

  const internetGateway = new aws.ec2.InternetGateway(
    environment.resourceName("internet-gateway"),
    { vpcId: vpc.id },
  );
  const publicRouteTable = new aws.ec2.RouteTable(
    environment.resourceName("public-routes"),
    {
      vpcId: vpc.id,
      routes: [{ cidrBlock: "0.0.0.0/0", gatewayId: internetGateway.id }],
    },
  );

  const publicSubnets = createSubnets(
    config,
    vpc,
    "public",
    config.publicSubnetCidrs,
    true,
    availabilityZones,
  );
  const privateSubnets = createSubnets(
    config,
    vpc,
    "private",
    config.privateSubnetCidrs,
    false,
    availabilityZones,
  );

  publicSubnets.forEach((subnet, index) => {
    new aws.ec2.RouteTableAssociation(
      environment.resourceName(`public-route-${index + 1}`),
      { subnetId: subnet.id, routeTableId: publicRouteTable.id },
    );
  });

  const natSubnetCount = environment.name === "production" ? 2 : 1;
  const natGateways = publicSubnets.slice(0, natSubnetCount).map((subnet, index) => {
    const elasticIp = new aws.ec2.Eip(
      environment.resourceName(`nat-ip-${index + 1}`),
      { domain: "vpc" },
    );

    return new aws.ec2.NatGateway(
      environment.resourceName(`nat-${index + 1}`),
      { allocationId: elasticIp.id, subnetId: subnet.id },
      { dependsOn: internetGateway },
    );
  });

  privateSubnets.forEach((subnet, index) => {
    const natGateway = environment.name === "production"
      ? natGateways[index]
      : natGateways[0];
    const routeTable = new aws.ec2.RouteTable(
      environment.resourceName(`private-routes-${index + 1}`),
      {
        vpcId: vpc.id,
        routes: [{ cidrBlock: "0.0.0.0/0", natGatewayId: natGateway.id }],
      },
    );

    new aws.ec2.RouteTableAssociation(
      environment.resourceName(`private-route-${index + 1}`),
      { subnetId: subnet.id, routeTableId: routeTable.id },
    );
  });

  const loadBalancerSecurityGroup = new aws.ec2.SecurityGroup(
    environment.resourceName("load-balancer-sg"),
    {
      vpcId: vpc.id,
      ingress: [
        { protocol: "tcp", fromPort: 80, toPort: 80, cidrBlocks: ["0.0.0.0/0"] },
        { protocol: "tcp", fromPort: 443, toPort: 443, cidrBlocks: ["0.0.0.0/0"] },
      ],
      egress: [{ protocol: "-1", fromPort: 0, toPort: 0, cidrBlocks: ["0.0.0.0/0"] }],
    },
  );
  const applicationSecurityGroup = new aws.ec2.SecurityGroup(
    environment.resourceName("application-sg"),
    {
      vpcId: vpc.id,
      ingress: [{
        protocol: "tcp",
        fromPort: 8080,
        toPort: 8080,
        securityGroups: [loadBalancerSecurityGroup.id],
      }],
      egress: [{ protocol: "-1", fromPort: 0, toPort: 0, cidrBlocks: ["0.0.0.0/0"] }],
    },
  );
  const databaseSecurityGroup = privateServiceSecurityGroup(
    config,
    vpc,
    applicationSecurityGroup,
    "database",
    5432,
  );
  const redisSecurityGroup = privateServiceSecurityGroup(
    config,
    vpc,
    applicationSecurityGroup,
    "redis",
    6379,
  );

  return {
    vpc,
    publicSubnets,
    privateSubnets,
    loadBalancerSecurityGroup,
    applicationSecurityGroup,
    databaseSecurityGroup,
    redisSecurityGroup,
  };
}

function createSubnets(
  config: PlatformConfig,
  vpc: aws.ec2.Vpc,
  visibility: "public" | "private",
  cidrs: [string, string],
  mapPublicIpOnLaunch: boolean,
  availabilityZones: ReturnType<typeof aws.getAvailabilityZonesOutput>,
): [aws.ec2.Subnet, aws.ec2.Subnet] {
  return cidrs.map((cidrBlock, index) => new aws.ec2.Subnet(
    config.environment.resourceName(`${visibility}-${index + 1}`),
    {
      vpcId: vpc.id,
      cidrBlock,
      availabilityZone: availabilityZones.names.apply((names) => {
        const availabilityZone = names[index];

        if (!availabilityZone) {
          throw new Error("The configured AWS region requires at least two availability zones.");
        }

        return availabilityZone;
      }),
      mapPublicIpOnLaunch,
      tags: { Name: config.environment.resourceName(`${visibility}-${index + 1}`) },
    },
  )) as [aws.ec2.Subnet, aws.ec2.Subnet];
}

function privateServiceSecurityGroup(
  config: PlatformConfig,
  vpc: aws.ec2.Vpc,
  applicationSecurityGroup: aws.ec2.SecurityGroup,
  component: string,
  port: number,
): aws.ec2.SecurityGroup {
  return new aws.ec2.SecurityGroup(
    config.environment.resourceName(`${component}-sg`),
    {
      vpcId: vpc.id,
      ingress: [{
        protocol: "tcp",
        fromPort: port,
        toPort: port,
        securityGroups: [applicationSecurityGroup.id],
      }],
      egress: [{ protocol: "-1", fromPort: 0, toPort: 0, cidrBlocks: ["0.0.0.0/0"] }],
    },
  );
}
