<?php

declare(strict_types=1);

 /**
 * @psalm-suppress RiskyTruthyFalsyComparison
 *
 * For details on how to configure the AWS SDK please read
 * https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/guide_configuration.html#credentials
 */
return [
    'aws' => [
        'endpoint' => getenv('AWS_DYNAMODB_ENDPOINT') ?: 'http://localstack:4566',
        'region' => getenv('AWS_REGION') ?: "eu-west-1",
    ],
];
