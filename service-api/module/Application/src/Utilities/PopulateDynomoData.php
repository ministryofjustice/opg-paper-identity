<?php

declare(strict_types=1);

namespace Application\Utilities;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;

use function Application\Utilities\loadData;

class PopulateDynomoData
{
    public function __construct(
        private readonly DynamoDbClient $dynamoDbClient,
    ) {
    }

    public function run()
    {
        $tableName = 'identity-verify';
        //load all the data
        $fileName = 'testData.json';
        $data = file_get_contents($fileName);

        $batch = json_decode($data);

        $this->writeBatch($tableName, $batch);

        //return some subset of data
        $nameKey = [
            'Key' => [
                'name' => [
                    'S' => "Joe Blogs",
                ],
            ],
        ];
        $result = $this->query($tableName, $nameKey);

        $marshal = new Marshaler();
        $output = $marshal->unmarshalItem($result['Items']);

        return $output;
    }

    public function writeBatch(string $TableName, array $Batch, int $depth = 2)
    {
        if (--$depth <= 0) {
            throw new Exception("Max depth exceeded. Please try with fewer batch items or increase depth.");
        }

        $marshal = new Marshaler();
        $total = 0;
        foreach (array_chunk($Batch, 25) as $Items) {
            foreach ($Items as $Item) {
                $BatchWrite['RequestItems'][$TableName][] = ['PutRequest' => ['Item' => $marshal->marshalItem($Item)]];
            }
            try {
                echo "Batching another " . count($Items) . " for a total of " . ($total += count($Items)) . " items!\n";
                $response = $this->dynamoDbClient->batchWriteItem($BatchWrite);
                $BatchWrite = [];
            } catch (\Exception $e) {
                echo "uh oh...";
                echo $e->getMessage();
                die();
            }
            if ($total >= 250) {
                echo "250 records is probably enough?\n";
                break;
            }
        }
    }

    public function query(string $tableName, $key)
    {
        $expressionAttributeValues = [];
        $expressionAttributeNames = [];
        $keyConditionExpression = "";
        $index = 1;
        foreach ($key as $name => $value) {
            $keyConditionExpression .= "#" . array_key_first($value) . " = :v$index,";
            $expressionAttributeNames["#" . array_key_first($value)] = array_key_first($value);
            $hold = array_pop($value);
            $expressionAttributeValues[":v$index"] = [
                array_key_first($hold) => array_pop($hold),
            ];
        }
        $keyConditionExpression = substr($keyConditionExpression, 0, -1);
        $query = [
            'ExpressionAttributeValues' => $expressionAttributeValues,
            'ExpressionAttributeNames' => $expressionAttributeNames,
            'KeyConditionExpression' => $keyConditionExpression,
            'TableName' => $tableName,
        ];
        return $this->dynamoDbClient->query($query);
    }

    public function createTable(string $tableName, array $attributes)
    {
        $keySchema = [];
        $attributeDefinitions = [];
        foreach ($attributes as $attribute) {
            if (is_a($attribute, DynamoDBAttribute::class)) {
                $keySchema[] = ['AttributeName' => $attribute->AttributeName, 'KeyType' => $attribute->KeyType];
                $attributeDefinitions[] =
                    ['AttributeName' => $attribute->AttributeName, 'AttributeType' => $attribute->AttributeType];
            }
        }

        $this->dynamoDbClient->createTable([
            'TableName' => $tableName,
            'KeySchema' => $keySchema,
            'AttributeDefinitions' => $attributeDefinitions,
            'ProvisionedThroughput' => ['ReadCapacityUnits' => 10, 'WriteCapacityUnits' => 10],
        ]);
    }
}
