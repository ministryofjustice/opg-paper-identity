<?php

declare(strict_types=1);

namespace Application\Fixtures;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Aws\Exception\AwsException;

class DataImportHandler
{
    final const DATA_FILE_PATH = __DIR__ . '/Data/sampleData.json';

    public function __construct(
        private readonly DynamoDbClient $dynamoDbClient,
        private readonly string $tableName,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function load(): void
    {
        if (!file_exists(self::DATA_FILE_PATH) || !is_readable(self::DATA_FILE_PATH)) {
            throw new \Exception("File does not exist or is not readable");
        }

        $data = file_get_contents(self::DATA_FILE_PATH);

        if ($data === false) {
            throw new \Exception("Failed to read JSON data");
        }

        $batch = json_decode($data);

        $this->importData($batch);

    }

    public function importData($data): void
    {
        $marshal = new Marshaler();

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

}
