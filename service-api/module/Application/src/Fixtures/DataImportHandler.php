<?php

declare(strict_types=1);

namespace Application\Fixtures;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;

class DataImportHandler
{
    final const DATA_FILE_PATH = __DIR__ . '/Data/sampleData.json';

    public function __construct(
        private readonly DynamoDbClient $dynamoDbClient,
        private readonly string $tableName,
    ) {
    }

    public function load()
    {
        $data = file_get_contents(self::DATA_FILE_PATH);
        $batch = json_decode($data);

        $this->writeBatch($this->tableName, $batch);

        //return all data
        $result = $this->dynamoDbClient->scan(array('TableName' => $this->tableName));
        $displayResults = [];
        foreach ($result['Items'] as $record) {

            $marshal = new Marshaler();
            $displayResults[] = $marshal->unmarshalItem($record);
        }
        return $displayResults;
    }

    /**
     * @throws \Exception
     */
    public function writeBatch(string $TableName, array $Batch, int $depth = 2)
    {
        if (--$depth <= 0) {
            throw new \Exception("Max depth exceeded. Please try with fewer batch items or increase depth.");
        }

        $marshal = new Marshaler();
        $total = 0;
        foreach (array_chunk($Batch, 25) as $Items) {
            foreach ($Items as $Item) {
                $BatchWrite['RequestItems'][$TableName][] = ['PutRequest' => ['Item' => $marshal->marshalItem($Item)]];
            }
            try {
                echo "Batching " . count($Items) . " for a total of " . ($total += count($Items)) . " items!\n";
                $response = $this->dynamoDbClient->batchWriteItem($BatchWrite);
                $BatchWrite = [];
            } catch (\Exception $e) {
                echo "uh oh...";
                echo $e->getMessage();
                die();
            }
        }
    }
}
