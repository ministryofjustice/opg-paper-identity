<?php

declare(strict_types=1);

namespace Application\Fixtures;

use Application\Model\Entity\CaseData;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Marshaler;
use Aws\Exception\AwsException;
use Psr\Log\LoggerInterface;

class DataImportHandler
{
    public final const DATA_FILE_PATH = __DIR__ . '/Data/sampleData.json';

    public function __construct(
        private readonly DynamoDbClient $dynamoDbClient,
        private readonly string $tableName,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function insertData(CaseData $item): void
    {
        $marsheler = new Marshaler();
        $encoded = $marsheler->marshalItem($item->jsonSerialize());

        $params = [
            'TableName' => $this->tableName,
            'Item' => $encoded
        ];

        try {
            $this->dynamoDbClient->putItem($params);
        } catch (AwsException $e) {
            $this->logger->error('Unable to save data [' . $e->getMessage() . '] to ' . $this->tableName, [
                'data' => $item
            ]);
        }
    }

    public function updateCaseData(string $uuid, string $attrName, string $attrType, string $attrValue): void
    {
        $idKey = [
            'key' => [
                'id' => [
                    'S' => $uuid,
                ],
            ],
        ];
        try {
            $this->dynamoDbClient->updateItem([
                'Key' => $idKey['key'],
                'TableName' => $this->tableName,
                'UpdateExpression' => "set #NV=:NV",
                'ExpressionAttributeNames' => [
                    '#NV' => $attrName,
                ],
                'ExpressionAttributeValues' => [
                    ':NV' => [
                        $attrType => $attrValue
                    ]
                ],
            ]);
        } catch (AwsException $e) {
            $this->logger->error('Unable to update data [' . $e->getMessage() . '] for case' . $uuid, [
                'data' => [$attrName => $attrValue]
            ]);
        }
    }
}
