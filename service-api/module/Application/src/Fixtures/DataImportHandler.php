<?php

declare(strict_types=1);

namespace Application\Fixtures;

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

    /**
     * @throws \Exception
     */
    public function load(): void
    {
        if (! file_exists(self::DATA_FILE_PATH) || ! is_readable(self::DATA_FILE_PATH)) {
            throw new \Exception("File does not exist or is not readable");
        }
        $data = file_get_contents(self::DATA_FILE_PATH);

        if ($data === false) {
            throw new \Exception("Failed to read JSON data");
        }
        /**
         * @var mixed $batch
         */
        $batch = json_decode($data);
        if (is_array($batch)) {
            $this->importData($batch);
        }
    }

    public function importData(array $data): void
    {
        $marshal = new Marshaler();
        /**
         * @var array $item
         */
        foreach ($data as $item) {
            $params = [
                'TableName' => $this->tableName,
                'Item' => $marshal->marshalItem($item)
            ];

            try {
                $this->dynamoDbClient->putItem($params);
            } catch (AwsException $e) {
                // Handle errors
                echo "Error: " . $e->getMessage();
            }
        }
    }

    public function insertData(string $tablename, array $item): void
    {
        $params = [
            'TableName' => $tablename,
            'Item' => $item
        ];
        try {
            $this->dynamoDbClient->putItem($params);
        } catch (AwsException $e) {
            $this->logger->error('Unable to save data [' . $e->getMessage() . '] to ' . $tablename, [
                'data' => $item
            ]);
        }
    }

    function updateCaseWithQuestions($uuid, $attributeName, $attributeValue) : void
    {
        $params = [
            'TableName' => 'cases',
            'Key' => [
                'id' => [
                    'S' => $uuid,
                ],
            ],
            'UpdateExpression' => 'SET #attrName = :attrValue',
            'ExpressionAttributeNames' => [
                '#attrName' => 'kbvQuestions'
            ],
            'ExpressionAttributeValues' => [
                ':attrValue' => ['L' => []]
            ],
            'ReturnValues' => 'UPDATED_NEW',
        ];

        foreach ($attributeValue as $value) {
            $params['ExpressionAttributeValues'][':attrValue']['L'][] = [
                'M' => [
                    'number' => ['S' => $value['number']],
                    'question' => ['S' => $value['question']],
                    'prompts' => ['L' => array_map(function($prompt) { return ['S' => $prompt]; }, $value['prompts'])],
                    'answer' => ['S' => $value['answer']]
                ]
            ];
        }
        try {
            $result = $this->dynamoDbClient->updateItem($params);
            echo "Item updated successfully: " . $result['Attributes']['id']['S'] . "\n";
        } catch (DynamoDbException $e) {
            echo "Unable to update item: " . $e->getMessage() . "\n"; die;
        }
    }

    public function updateCaseData(string $uuid, string $attributeName, string $attributeType, string $attributeValue): void
    {
        $idKey = [
            'key' => [
                'id' => [
                    'S' => $uuid,
                ],
            ],
        ];

        try {
            $this->updateItemAttributeByKey('cases', $idKey, $attributeName, $attributeType, $attributeValue);
        } catch (AwsException $e) {
            //var_dump($e->getMessage()); die;
            $this->logger->error('Unable to update data [' . $e->getMessage() . '] for case' . $uuid, [
                'data' => [$attributeName => $attributeValue]
            ]);
        }
    }

    public function updateItemAttributeByKey(
        string $tableName,
        array $key,
        string $attributeName,
        string $attributeType,
        string $newValue
    ): void {
        $this->dynamoDbClient->updateItem([
            'Key' => $key['key'],
            'TableName' => $tableName,
            'UpdateExpression' => "set #NV=:NV",
            'ExpressionAttributeNames' => [
                '#NV' => $attributeName,
            ],
            'ExpressionAttributeValues' => [
                ':NV' => [
                    $attributeType => $newValue
                ]
            ],
        ]);
    }
}
