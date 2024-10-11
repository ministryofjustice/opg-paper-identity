<?php

declare(strict_types=1);

namespace Telemetry\Instrumentation;

use Aws\AwsClient;
use Aws\DynamoDb\DynamoDbClient;
use Aws\ResultInterface;
use Aws\S3\S3Client;
use Aws\Sqs\SqsClient;
use OpenTelemetry\API\Instrumentation\CachedInstrumentation;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SDK\Trace\Span;
use OpenTelemetry\SemConv\TraceAttributes;
use Throwable;

use function OpenTelemetry\Instrumentation\hook;

class Aws
{
    public static function register(): void
    {
        $instrumentation = new CachedInstrumentation(
            'io.opentelemetry.contrib.php.aws',
            null,
            'https://opentelemetry.io/schemas/1.24.0'
        );

        /** @psalm-suppress UnusedFunctionCall */
        hook(
            AwsClient::class,
            '__call',
            pre: static function (AwsClient $client, array $params) use ($instrumentation) {
                $parentContext = Context::getCurrent();

                $clientName = $client->getApi()->getServiceName();
                $region = $client->getRegion();

                $command = $params[0];
                $args = $params[1][0];

                $spanBuilder = $instrumentation->tracer()
                    ->spanBuilder($client->getApi()->getServiceName() ?: 'UnknownAWSService')
                    ->setParent($parentContext)
                    ->setSpanKind(SpanKind::KIND_CLIENT)
                    ->setAttributes([
                        'rpc.method' => $command,
                        'rpc.service' => $clientName,
                        'rpc.system' => 'aws-api',
                        'aws.region' => $region,
                    ]);

                if ($client instanceof DynamoDbClient && isset($args['TableName'])) {
                    $spanBuilder->setAttribute(TraceAttributes::AWS_DYNAMODB_TABLE_NAMES, [$args['TableName']]);
                }

                if ($client instanceof S3Client) {
                    if (isset($args['Bucket'])) {
                        $spanBuilder->setAttribute(TraceAttributes::AWS_S3_BUCKET, $args['Bucket']);
                    }

                    if (isset($args['CopySource'])) {
                        $spanBuilder->setAttribute(TraceAttributes::AWS_S3_COPY_SOURCE, $args['CopySource']);
                    }

                    if (isset($args['Key'])) {
                        $spanBuilder->setAttribute(TraceAttributes::AWS_S3_KEY, $args['Key']);
                    }
                }

                if ($client instanceof SqsClient) {
                    if (isset($args['QueueUrl'])) {
                        $spanBuilder->setAttribute('aws.queue_url', $args['QueueUrl']);
                    }
                }

                $span = $spanBuilder->startSpan();

                $context = $span->storeInContext($parentContext);
                Context::storage()->attach($context);
            },
            post: static function (AwsClient $client, array $params, mixed $result, ?Throwable $exception): void {
                $scope = Context::storage()->scope();
                $scope?->detach();

                if (! $scope) {
                    return;
                }

                $span = Span::fromContext($scope->context());

                if ($exception) {
                    $span->recordException($exception, [TraceAttributes::EXCEPTION_ESCAPED => true]);
                    $span->setStatus(StatusCode::STATUS_ERROR, $exception->getMessage());
                }

                if ($result instanceof ResultInterface) {
                    if (isset($result['@metadata'])) {
                        $span->setAttribute(
                            TraceAttributes::HTTP_RESPONSE_STATUS_CODE,
                            $result['@metadata']['statusCode']
                        );
                    }
                }

                $span->end();
            }
        );
    }
}
