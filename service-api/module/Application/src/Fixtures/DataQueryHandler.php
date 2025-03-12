<?php

declare(strict_types=1);

namespace Application\Fixtures;

use Application\Model\Entity\CaseData;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
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

    public function healthCheck(): bool
    {
        try {
            $this->dynamoDbClient->describeTable([
                'TableName' => $this->tableName,
            ]);
            return true;
        } catch (DynamoDbException $exception) {
            //Log exception here?
            return false;
        }
    }

    public function getCaseByUUID(string $uuid): ?CaseData
    {
        $idKey = [
            'key' => [
                'id' => [
                    'S' => $uuid,
                ],
            ],
        ];

        $result = $this->query($idKey);

        $arr = $this->returnUnmarshalResult($result)[0];

        return $arr ? CaseData::fromArray($arr) : null;
    }

    public function queryByYotiSessionId(string $sessionId): ?CaseData
    {
        $idKey = [
            'key' => [
                'yotiSessionId' => [
                    'S' => $sessionId,
                ],
            ],
        ];
        $index = "yotiSessionId-index";
        $result = $this->query($idKey, $index);

        $array = $this->returnUnmarshalResult($result)[0];

        return $array ? CaseData::fromArray($array) : null;
    }

    /**
     * @param array<string, mixed> $key
     * @param string $dbIndex
     * @return Result
     */
    public function query($key, $dbIndex = ''): Result
    {
        $expressionAttributeValues = [];
        $expressionAttributeNames = [];
        $keyConditionExpression = "";
        $index = 1;

        foreach ($key as $value) {
            if (! is_array($value)) {
                throw new InvalidJsonException("Key value must be an array.");
            }

            $keyName = array_key_first($value);
            if (! is_string($keyName)) {
                throw new InvalidJsonException("Could not extract key name");
            }

            $keyConditionExpression .= "#" . $keyName . " = :v$index AND ";
            $expressionAttributeNames["#" . $keyName] = $keyName;

            /** @var array $hold */
            $hold = array_pop($value);
            $expressionAttributeValues[":v$index"] = [];

            $key = array_key_first($hold);
            $value = array_pop($hold);

            if ($key !== null && $value !== null) {
                $expressionAttributeValues[":v$index"][$key] = $value;
            }

            $index++;
        }
        $keyConditionExpression = rtrim($keyConditionExpression, " AND ");

        $query = [
            'ExpressionAttributeValues' => $expressionAttributeValues,
            'ExpressionAttributeNames' => $expressionAttributeNames,
            'KeyConditionExpression' => $keyConditionExpression,
            'TableName' => $this->tableName,
            'IndexName' => $dbIndex,
        ];

        if ($dbIndex === '') {
            unset($query['IndexName']);
        }

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
                $json = $marshal->unmarshalJson($record);
                $displayResults[] = json_decode($json, true);
            }
        }
        return $displayResults;
    }
}
