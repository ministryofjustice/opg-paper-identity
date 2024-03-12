<?php

declare(strict_types=1);

namespace Application\Fixtures;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Aws\Result;
use Aws\Exception\InvalidJsonException;

class DataQueryHandler
{
    public function __construct(
        private readonly DynamoDbClient $dynamoDbClient,
        private readonly string $tableName,
    ) {
    }

    public function returnAll(): array
    {
        $result = $this->dynamoDbClient->scan(['TableName' => $this->tableName]);

        return $this->returnUnmarshalResult($result);
    }

    public function queryByName(string $name): array
    {
        $nameKey = [
            'KEY' => [
                'name' => [
                    'S' => $name,
                ],
            ],
        ];
        $index = "name-index";
        $result = $this->query($this->tableName, $nameKey, $index);

        return $this->returnUnmarshalResult($result);
    }

    public function queryByIDNumber(string $idNumber): array
    {
        //return some subset of data
        $idKey = [
            'key' => [
                'id_number' => [
                    'S' => $idNumber,
                ],
            ],
        ];
        $index = "id_number-index";
        $result = $this->query($this->tableName, $idKey, $index);

        return $this->returnUnmarshalResult($result);
    }

    /**
     * @psalm-suppress MixedInferredReturnType
     * @psalm-suppress MixedReturnStatement
     */
    public function searchByPrimaryId(int $value): ?Array
    {
        $params = [
            'TableName' => $this->tableName,
            'Key' => ['id' => ['N' => $value]],
        ];

        // Get item from DynamoDB table
        $result = $this->dynamoDbClient->getItem($params);

        return $result['Item'];
    }
    /**
     * @param string $tableName
     * @param array<string, mixed> $key
     * @param string $dbIndex
     * @return Result
     */
    public function query(string $tableName, $key, $dbIndex = ''): Result
    {
        $expressionAttributeValues = [];
        $expressionAttributeNames = [];
        $keyConditionExpression = "";
        $index = 1;
        foreach ($key as $name => $value) {
            if (!is_array($value)) {
                throw new InvalidJsonException("Key value must be an array.");
            }
            $keyConditionExpression .= "#" . array_key_first($value) . " = :v$index AND ";
            $expressionAttributeNames["#" . array_key_first($value)] = array_key_first($value);

            /** @var array $hold */
            $hold = array_pop($value);
            $expressionAttributeValues[":v$index"] = [
                array_key_first($hold) => array_pop($hold),
            ];

            $index++;
        }
        $keyConditionExpression = substr($keyConditionExpression, 0, -5);
        $query = [
            'ExpressionAttributeValues' => $expressionAttributeValues,
            'ExpressionAttributeNames' => $expressionAttributeNames,
            'KeyConditionExpression' => $keyConditionExpression,
            'TableName' => $tableName,
            'IndexName' => $dbIndex,
        ];

        return $this->dynamoDbClient->query($query);
    }

    public function returnUnmarshalResult(Result $result): array
    {
        $result = $result->toArray();
        $displayResults = [];
        if (isset($result['Items'])) {
            /** @var array $record */
            foreach ($result['Items'] as $record) {
                $marshal = new Marshaler();
                $displayResults[] = $marshal->unmarshalItem($record);
            }
        }
        return $displayResults;
    }
}
