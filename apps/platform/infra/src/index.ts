import * as pulumi from "@pulumi/pulumi";
import { createApplicationCompute } from "./compute.js";
import { loadPlatformConfig } from "./config.js";
import { createDataStores } from "./data.js";
import { createDocumentInfrastructure } from "./documents.js";
import { createNetwork } from "./network.js";
import { createApplicationSecrets } from "./secrets.js";

const config = loadPlatformConfig();
const network = createNetwork(config);
const dataStores = createDataStores(config, network);
const documents = createDocumentInfrastructure(config, dataStores);
const secrets = createApplicationSecrets(config, dataStores);
const compute = createApplicationCompute({
  config,
  network,
  dataStores,
  documents,
  secrets,
});

export const environment = config.environment.name;
export const applicationUrl = pulumi.interpolate`https://${config.domainName}`;
export const loadBalancerDnsName = compute.loadBalancer.dnsName;
export const clusterName = compute.cluster.name;
export const repositoryUrl = compute.repository.repositoryUrl;
export const databaseIdentifier = dataStores.database.identifier;
export const documentsBucketName = documents.bucket.bucket;
export const textractResultsQueueUrl = documents.resultsQueue.url;
export const webTaskDefinitionArn = compute.taskDefinitions.web.arn;
export const privateSubnetIds = network.privateSubnets.map((subnet) => subnet.id);
export const applicationSecurityGroupId = network.applicationSecurityGroup.id;
export const serviceNames = Object.fromEntries(
  Object.entries(compute.services).map(([name, service]) => [name, service.name]),
);
