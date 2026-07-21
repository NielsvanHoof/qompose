import * as aws from "@pulumi/aws";
import * as pulumi from "@pulumi/pulumi";
import { PlatformConfig } from "./config.js";
import { PlatformDataStores } from "./data.js";
import { DocumentInfrastructure } from "./documents.js";
import { PlatformNetwork } from "./network.js";
import { ApplicationSecrets } from "./secrets.js";

type ProcessName = "web" | "queue" | "reverb" | "textract" | "scheduler";

export interface ApplicationCompute {
  cluster: aws.ecs.Cluster;
  repository: aws.ecr.Repository;
  loadBalancer: aws.lb.LoadBalancer;
  taskDefinitions: Record<ProcessName, aws.ecs.TaskDefinition>;
  services: Record<ProcessName, aws.ecs.Service>;
}

interface ComputeInputs {
  config: PlatformConfig;
  network: PlatformNetwork;
  dataStores: PlatformDataStores;
  documents: DocumentInfrastructure;
  secrets: ApplicationSecrets;
}

export function createApplicationCompute(inputs: ComputeInputs): ApplicationCompute {
  const { config, network } = inputs;
  const { environment } = config;
  const cluster = new aws.ecs.Cluster(environment.resourceName("cluster"), {
    name: environment.resourceName("cluster"),
    settings: [{ name: "containerInsights", value: "enabled" }],
  });
  const repository = new aws.ecr.Repository(environment.resourceName("repository"), {
    name: environment.resourceName("application"),
    imageTagMutability: "IMMUTABLE",
    forceDelete: environment.name === "staging",
    imageScanningConfiguration: { scanOnPush: true },
    encryptionConfigurations: [{ encryptionType: "AES256" }],
  });
  new aws.ecr.LifecyclePolicy(environment.resourceName("repository-lifecycle"), {
    repository: repository.name,
    policy: JSON.stringify({
      rules: [{
        rulePriority: 1,
        description: "Retain the most recent 30 application images",
        selection: {
          tagStatus: "any",
          countType: "imageCountMoreThan",
          countNumber: 30,
        },
        action: { type: "expire" },
      }],
    }),
  });
  const executionRole = createExecutionRole(inputs);
  const taskRole = createTaskRole(inputs);
  const logGroups = createLogGroups(config);
  const taskDefinitions = createTaskDefinitions(
    inputs,
    executionRole,
    taskRole,
    logGroups,
  );
  const loadBalancer = new aws.lb.LoadBalancer(environment.resourceName("load-balancer"), {
    name: environment.resourceName("load-balancer"),
    loadBalancerType: "application",
    internal: false,
    securityGroups: [network.loadBalancerSecurityGroup.id],
    subnets: network.publicSubnets.map((subnet) => subnet.id),
    enableDeletionProtection: environment.deletionProtection,
    dropInvalidHeaderFields: true,
    idleTimeout: 3600,
  });
  const webTarget = createTargetGroup(inputs, "web", "/ready", "200");
  const reverbTarget = createTargetGroup(inputs, "reverb", "/", "200-499");
  const httpsListener = new aws.lb.Listener(environment.resourceName("https-listener"), {
    loadBalancerArn: loadBalancer.arn,
    port: 443,
    protocol: "HTTPS",
    certificateArn: config.certificateArn,
    sslPolicy: "ELBSecurityPolicy-TLS13-1-2-2021-06",
    defaultActions: [{ type: "forward", targetGroupArn: webTarget.arn }],
  });
  new aws.lb.Listener(environment.resourceName("http-listener"), {
    loadBalancerArn: loadBalancer.arn,
    port: 80,
    protocol: "HTTP",
    defaultActions: [{
      type: "redirect",
      redirect: { protocol: "HTTPS", port: "443", statusCode: "HTTP_301" },
    }],
  });
  new aws.lb.ListenerRule(environment.resourceName("reverb-listener-rule"), {
    listenerArn: httpsListener.arn,
    priority: 10,
    actions: [{ type: "forward", targetGroupArn: reverbTarget.arn }],
    conditions: [{ pathPattern: { values: ["/app/*", "/apps/*"] } }],
  });

  const services = {
    web: createService(inputs, cluster, taskDefinitions.web, "web", environment.webDesiredCount, {
      targetGroup: webTarget,
      dependsOn: httpsListener,
    }),
    queue: createService(inputs, cluster, taskDefinitions.queue, "queue", environment.workerDesiredCount),
    reverb: createService(
      inputs,
      cluster,
      taskDefinitions.reverb,
      "reverb",
      environment.reverbDesiredCount,
      { targetGroup: reverbTarget, dependsOn: httpsListener },
    ),
    textract: createService(
      inputs,
      cluster,
      taskDefinitions.textract,
      "textract",
      environment.textractConsumerDesiredCount,
    ),
    scheduler: createService(inputs, cluster, taskDefinitions.scheduler, "scheduler", 1),
  };
  createServiceAutoScaling(config, cluster, services);
  createOperationalAlarms(inputs, loadBalancer);

  if (config.hostedZoneId) {
    new aws.route53.Record(environment.resourceName("application-dns"), {
      zoneId: config.hostedZoneId,
      name: config.domainName,
      type: "A",
      aliases: [{
        name: loadBalancer.dnsName,
        zoneId: loadBalancer.zoneId,
        evaluateTargetHealth: true,
      }],
    });
  }

  return { cluster, repository, loadBalancer, taskDefinitions, services };
}

function createExecutionRole(inputs: ComputeInputs): aws.iam.Role {
  const { environment } = inputs.config;
  const role = new aws.iam.Role(environment.resourceName("execution-role"), {
    assumeRolePolicy: ecsAssumeRolePolicy().json,
  });
  new aws.iam.RolePolicyAttachment(environment.resourceName("execution-policy-attachment"), {
    role: role.name,
    policyArn: aws.iam.ManagedPolicy.AmazonECSTaskExecutionRolePolicy,
  });
  new aws.iam.RolePolicy(environment.resourceName("execution-secrets-policy"), {
    role: role.id,
    policy: aws.iam.getPolicyDocumentOutput({
      statements: [{
        actions: ["secretsmanager:GetSecretValue"],
        resources: [inputs.secrets.secret.arn, inputs.secrets.databaseSecretArn],
      }, {
        actions: ["kms:Decrypt"],
        resources: [inputs.dataStores.encryptionKey.arn],
      }],
    }).json,
  });

  return role;
}

function createTaskRole(inputs: ComputeInputs): aws.iam.Role {
  const { environment } = inputs.config;
  const role = new aws.iam.Role(environment.resourceName("task-role"), {
    assumeRolePolicy: ecsAssumeRolePolicy().json,
  });
  const bucketObjects = inputs.documents.bucket.arn.apply((arn) => `${arn}/*`);
  new aws.iam.RolePolicy(environment.resourceName("task-policy"), {
    role: role.id,
    policy: aws.iam.getPolicyDocumentOutput({
      statements: [{
        actions: ["s3:ListBucket"],
        resources: [inputs.documents.bucket.arn],
      }, {
        actions: ["s3:GetObject", "s3:PutObject", "s3:DeleteObject"],
        resources: [bucketObjects],
      }, {
        actions: ["textract:StartDocumentAnalysis", "textract:GetDocumentAnalysis"],
        resources: ["*"],
      }, {
        actions: ["iam:PassRole"],
        resources: [inputs.documents.textractPublishRole.arn],
        conditions: [{
          test: "StringEquals",
          variable: "iam:PassedToService",
          values: ["textract.amazonaws.com"],
        }],
      }, {
        actions: [
          "sqs:ReceiveMessage",
          "sqs:DeleteMessage",
          "sqs:GetQueueAttributes",
          "sqs:ChangeMessageVisibility",
        ],
        resources: [inputs.documents.resultsQueue.arn],
      }, {
        actions: ["ses:SendEmail", "ses:SendRawEmail"],
        resources: ["*"],
      }, {
        actions: ["kms:Decrypt", "kms:Encrypt", "kms:GenerateDataKey"],
        resources: [inputs.dataStores.encryptionKey.arn],
      }],
    }).json,
  });

  return role;
}

function createLogGroups(
  config: PlatformConfig,
): Record<ProcessName, aws.cloudwatch.LogGroup> {
  const retentionInDays = config.environment.name === "production" ? 90 : 30;

  return Object.fromEntries(
    (["web", "queue", "reverb", "textract", "scheduler"] as ProcessName[])
      .map((process) => [process, new aws.cloudwatch.LogGroup(
        config.environment.resourceName(`${process}-logs`),
        {
          name: `/qompose/${config.environment.name}/${process}`,
          retentionInDays,
        },
      )]),
  ) as Record<ProcessName, aws.cloudwatch.LogGroup>;
}

function createTaskDefinitions(
  inputs: ComputeInputs,
  executionRole: aws.iam.Role,
  taskRole: aws.iam.Role,
  logGroups: Record<ProcessName, aws.cloudwatch.LogGroup>,
): Record<ProcessName, aws.ecs.TaskDefinition> {
  const commands: Record<ProcessName, string[]> = {
    web: ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/qompose.conf"],
    queue: ["php", "artisan", "queue:work", "redis", "--sleep=1", "--tries=3", "--timeout=120", "--max-time=3600", "--no-interaction"],
    reverb: ["php", "artisan", "reverb:start", "--host=0.0.0.0", "--port=8080", "--no-interaction"],
    textract: ["php", "artisan", "textract:consume"],
    scheduler: ["php", "artisan", "schedule:work", "--no-interaction"],
  };

  return Object.fromEntries(
    (Object.keys(commands) as ProcessName[]).map((process) => [
      process,
      new aws.ecs.TaskDefinition(inputs.config.environment.resourceName(`${process}-task`), {
        family: inputs.config.environment.resourceName(process),
        cpu: "512",
        memory: "1024",
        networkMode: "awsvpc",
        requiresCompatibilities: ["FARGATE"],
        executionRoleArn: executionRole.arn,
        taskRoleArn: taskRole.arn,
        runtimePlatform: { operatingSystemFamily: "LINUX", cpuArchitecture: "X86_64" },
        containerDefinitions: containerDefinitions(inputs, process, commands[process], logGroups[process]),
      }),
    ]),
  ) as Record<ProcessName, aws.ecs.TaskDefinition>;
}

function containerDefinitions(
  inputs: ComputeInputs,
  process: ProcessName,
  command: string[],
  logGroup: aws.cloudwatch.LogGroup,
): pulumi.Output<string> {
  const { config, dataStores, documents, secrets } = inputs;
  const region = aws.config.region ?? "eu-west-1";

  return pulumi.all([
    dataStores.database.address,
    dataStores.redis.primaryEndpointAddress,
    documents.bucket.bucket,
    documents.textractTopic.arn,
    documents.textractPublishRole.arn,
    documents.resultsQueue.url,
    logGroup.name,
    secrets.secret.arn,
    secrets.databaseSecretArn,
  ]).apply(([
    databaseHost,
    redisHost,
    bucket,
    topicArn,
    textractRoleArn,
    resultsQueueUrl,
    logGroupName,
    applicationSecretArn,
    databaseSecretArn,
  ]) => JSON.stringify([{
    name: "application",
    image: config.imageUri,
    essential: true,
    command,
    portMappings: process === "web" || process === "reverb"
      ? [{ containerPort: 8080, hostPort: 8080, protocol: "tcp" }]
      : [],
    environment: applicationEnvironment(
      inputs,
      region,
      databaseHost,
      redisHost,
      bucket,
      topicArn,
      textractRoleArn,
      resultsQueueUrl,
    ),
    secrets: [
      { name: "APP_KEY", valueFrom: `${applicationSecretArn}:APP_KEY::` },
      { name: "REDIS_PASSWORD", valueFrom: `${applicationSecretArn}:REDIS_PASSWORD::` },
      { name: "REVERB_APP_ID", valueFrom: `${applicationSecretArn}:REVERB_APP_ID::` },
      { name: "REVERB_APP_KEY", valueFrom: `${applicationSecretArn}:REVERB_APP_KEY::` },
      { name: "REVERB_APP_SECRET", valueFrom: `${applicationSecretArn}:REVERB_APP_SECRET::` },
      { name: "DB_PASSWORD", valueFrom: `${databaseSecretArn}:password::` },
    ],
    logConfiguration: {
      logDriver: "awslogs",
      options: {
        "awslogs-group": logGroupName,
        "awslogs-region": region,
        "awslogs-stream-prefix": process,
      },
    },
    healthCheck: process === "web" ? {
      command: ["CMD-SHELL", "curl --fail --silent http://localhost:8080/up || exit 1"],
      interval: 30,
      timeout: 5,
      retries: 3,
      startPeriod: 30,
    } : undefined,
    stopTimeout: 60,
  }]));
}

function applicationEnvironment(
  inputs: ComputeInputs,
  region: string,
  databaseHost: string,
  redisHost: string,
  bucket: string,
  topicArn: string,
  textractRoleArn: string,
  resultsQueueUrl: string,
): Array<{ name: string; value: string }> {
  const { config } = inputs;
  const values: Record<string, string> = {
    APP_NAME: "Qompose",
    APP_ENV: "production",
    APP_DEBUG: "false",
    APP_URL: `https://${config.domainName}`,
    APP_LOCALE: "en",
    LOG_CHANNEL: "stderr",
    LOG_LEVEL: "info",
    DB_CONNECTION: "pgsql",
    DB_HOST: databaseHost,
    DB_PORT: "5432",
    DB_DATABASE: "qompose",
    DB_USERNAME: "qompose",
    DB_SSLMODE: "require",
    REDIS_CLIENT: "phpredis",
    REDIS_SCHEME: "tls",
    REDIS_HOST: redisHost,
    REDIS_PORT: "6379",
    CACHE_STORE: "redis",
    QUEUE_CONNECTION: "redis",
    SESSION_DRIVER: "redis",
    SESSION_ENCRYPT: "true",
    SESSION_SECURE_COOKIE: "true",
    SESSION_DOMAIN: config.domainName,
    TRUSTED_PROXIES: "REMOTE_ADDR",
    FILESYSTEM_DISK: "s3",
    FILESYSTEM_THROW: "true",
    FILESYSTEM_REPORT: "true",
    AWS_DEFAULT_REGION: region,
    AWS_BUCKET: bucket,
    AWS_USE_PATH_STYLE_ENDPOINT: "false",
    OCR_DRIVER: "textract",
    TEXTRACT_SNS_TOPIC_ARN: topicArn,
    TEXTRACT_SNS_ROLE_ARN: textractRoleArn,
    TEXTRACT_RESULTS_QUEUE_URL: resultsQueueUrl,
    BROADCAST_CONNECTION: "reverb",
    REVERB_HOST: config.domainName,
    REVERB_PORT: "443",
    REVERB_SCHEME: "https",
    MAIL_MAILER: "ses",
    MAIL_FROM_ADDRESS: config.mailFromAddress,
    MAIL_FROM_NAME: "Qompose",
    SCOUT_DRIVER: "database",
  };

  if (config.alarmEmailAddress) {
    values.HEALTH_NOTIFICATIONS_ENABLED = "true";
    values.HEALTH_TO_ADDRESS = config.alarmEmailAddress;
  }

  return Object.entries(values).map(([name, value]) => ({ name, value }));
}

function createTargetGroup(
  inputs: ComputeInputs,
  process: "web" | "reverb",
  healthPath: string,
  matcher: string,
): aws.lb.TargetGroup {
  return new aws.lb.TargetGroup(inputs.config.environment.resourceName(`${process}-target`), {
    name: inputs.config.environment.resourceName(`${process}-target`),
    port: 8080,
    protocol: "HTTP",
    targetType: "ip",
    vpcId: inputs.network.vpc.id,
    deregistrationDelay: 30,
    healthCheck: {
      enabled: true,
      path: healthPath,
      matcher,
      interval: 30,
      timeout: 10,
      healthyThreshold: 2,
      unhealthyThreshold: 3,
    },
  });
}

function createService(
  inputs: ComputeInputs,
  cluster: aws.ecs.Cluster,
  taskDefinition: aws.ecs.TaskDefinition,
  process: ProcessName,
  desiredCount: number,
  loadBalancer?: { targetGroup: aws.lb.TargetGroup; dependsOn: pulumi.Resource },
): aws.ecs.Service {
  const service = new aws.ecs.Service(inputs.config.environment.resourceName(`${process}-service`), {
    name: inputs.config.environment.resourceName(process),
    cluster: cluster.arn,
    taskDefinition: taskDefinition.arn,
    desiredCount: inputs.config.deployApplication ? desiredCount : 0,
    launchType: "FARGATE",
    platformVersion: "LATEST",
    enableEcsManagedTags: true,
    enableExecuteCommand: true,
    propagateTags: "SERVICE",
    deploymentMinimumHealthyPercent: 100,
    deploymentMaximumPercent: 200,
    deploymentCircuitBreaker: { enable: true, rollback: true },
    healthCheckGracePeriodSeconds: loadBalancer ? 90 : undefined,
    waitForSteadyState: true,
    networkConfiguration: {
      subnets: inputs.network.privateSubnets.map((subnet) => subnet.id),
      securityGroups: [inputs.network.applicationSecurityGroup.id],
      assignPublicIp: false,
    },
    loadBalancers: loadBalancer ? [{
      targetGroupArn: loadBalancer.targetGroup.arn,
      containerName: "application",
      containerPort: 8080,
    }] : undefined,
  }, loadBalancer ? { dependsOn: loadBalancer.dependsOn } : undefined);

  return service;
}

function createServiceAutoScaling(
  config: PlatformConfig,
  cluster: aws.ecs.Cluster,
  services: Record<ProcessName, aws.ecs.Service>,
): void {
  const scalableProcesses: Array<Exclude<ProcessName, "scheduler">> = [
    "web",
    "queue",
    "reverb",
    "textract",
  ];

  scalableProcesses.forEach((process) => {
    const service = services[process];
    const minimumCapacity = config.deployApplication
      ? (config.environment.name === "production" ? 2 : 1)
      : 0;
    const maximumCapacity = config.environment.name === "production" ? 8 : 2;
    const target = new aws.appautoscaling.Target(
      config.environment.resourceName(`${process}-scaling-target`),
      {
        maxCapacity: maximumCapacity,
        minCapacity: minimumCapacity,
        resourceId: pulumi.interpolate`service/${cluster.name}/${service.name}`,
        scalableDimension: "ecs:service:DesiredCount",
        serviceNamespace: "ecs",
      },
    );

    new aws.appautoscaling.Policy(
      config.environment.resourceName(`${process}-cpu-scaling`),
      {
        policyType: "TargetTrackingScaling",
        resourceId: target.resourceId,
        scalableDimension: target.scalableDimension,
        serviceNamespace: target.serviceNamespace,
        targetTrackingScalingPolicyConfiguration: {
          targetValue: process === "queue" || process === "textract" ? 60 : 65,
          predefinedMetricSpecification: {
            predefinedMetricType: "ECSServiceAverageCPUUtilization",
          },
          scaleInCooldown: 300,
          scaleOutCooldown: 60,
        },
      },
    );
  });
}

function createOperationalAlarms(
  inputs: ComputeInputs,
  loadBalancer: aws.lb.LoadBalancer,
): void {
  const { config, dataStores, documents } = inputs;
  const alarmActions: pulumi.Input<string>[] = [];

  if (config.alarmEmailAddress) {
    const topic = new aws.sns.Topic(config.environment.resourceName("operational-alarms"), {
      name: config.environment.resourceName("operational-alarms"),
      kmsMasterKeyId: dataStores.encryptionKey.arn,
    });
    new aws.sns.TopicSubscription(config.environment.resourceName("operational-alarm-email"), {
      topic: topic.arn,
      protocol: "email",
      endpoint: config.alarmEmailAddress,
    });
    alarmActions.push(topic.arn);
  }

  const common = {
    alarmActions,
    evaluationPeriods: 2,
    period: 300,
    statistic: "Average",
    treatMissingData: "notBreaching",
  };

  new aws.cloudwatch.MetricAlarm(config.environment.resourceName("application-5xx-alarm"), {
    ...common,
    comparisonOperator: "GreaterThanThreshold",
    threshold: 5,
    metricName: "HTTPCode_Target_5XX_Count",
    namespace: "AWS/ApplicationELB",
    statistic: "Sum",
    dimensions: { LoadBalancer: loadBalancer.arnSuffix },
  });
  new aws.cloudwatch.MetricAlarm(config.environment.resourceName("database-cpu-alarm"), {
    ...common,
    comparisonOperator: "GreaterThanThreshold",
    threshold: 80,
    metricName: "CPUUtilization",
    namespace: "AWS/RDS",
    dimensions: { DBInstanceIdentifier: dataStores.database.identifier },
  });
  new aws.cloudwatch.MetricAlarm(config.environment.resourceName("database-storage-alarm"), {
    ...common,
    comparisonOperator: "LessThanThreshold",
    threshold: 5 * 1024 * 1024 * 1024,
    metricName: "FreeStorageSpace",
    namespace: "AWS/RDS",
    statistic: "Minimum",
    dimensions: { DBInstanceIdentifier: dataStores.database.identifier },
  });
  new aws.cloudwatch.MetricAlarm(config.environment.resourceName("redis-cpu-alarm"), {
    ...common,
    comparisonOperator: "GreaterThanThreshold",
    threshold: 75,
    metricName: "EngineCPUUtilization",
    namespace: "AWS/ElastiCache",
    dimensions: { ReplicationGroupId: dataStores.redis.replicationGroupId },
  });
  new aws.cloudwatch.MetricAlarm(config.environment.resourceName("textract-dlq-alarm"), {
    ...common,
    comparisonOperator: "GreaterThanOrEqualToThreshold",
    threshold: 1,
    metricName: "ApproximateNumberOfMessagesVisible",
    namespace: "AWS/SQS",
    statistic: "Maximum",
    evaluationPeriods: 1,
    dimensions: { QueueName: documents.resultsDeadLetterQueue.name },
  });
}

function ecsAssumeRolePolicy(): pulumi.Output<aws.iam.GetPolicyDocumentResult> {
  return aws.iam.getPolicyDocumentOutput({
    statements: [{
      effect: "Allow",
      principals: [{ type: "Service", identifiers: ["ecs-tasks.amazonaws.com"] }],
      actions: ["sts:AssumeRole"],
    }],
  });
}
