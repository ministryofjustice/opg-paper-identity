<?php

declare(strict_types=1);

/*
 * For details on how to configure the AWS SDK please read
 * https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/guide_configuration.html#credentials
 */
return [
    'aws' => [
        'debug' => filter_var(getenv('PAPER_ID_BACK_AWS_DEBUG'), FILTER_VALIDATE_BOOLEAN),
        'endpoint' => getenv('PAPER_ID_BACK_AWS_ENDPOINT') ?: 'http://localstack:4566',
        'region' => getenv('AWS_REGION') ?: "eu-west-1",
    ],
];
