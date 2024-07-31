<?php

declare(strict_types=1);

namespace Application\Fixtures;

use Application\Model\Entity\CaseData;
use Application\Model\Entity\CounterService;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Marshaler;
use Aws\Exception\AwsException;
use InvalidArgumentException;
use Laminas\Form\Annotation\AttributeBuilder;
use Laminas\InputFilter\InputInterface;
use Psr\Log\LoggerInterface;

class DataImportHandler
{
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

    public function updateCaseData(string $uuid, string $attrName, string $attrType, mixed $attrValue): void
    {
        if (! property_exists(CaseData::class, $attrName)) {
            throw new InvalidArgumentException(sprintf('CaseData has no such property "%s"', $attrName));
        }

        $inputFilter = (new AttributeBuilder())
            ->createForm(CaseData::class)
            ->getInputFilter();
        $input = $inputFilter->get($attrName);

        if ($input instanceof InputInterface) {
            $input->setValue($attrValue);

            if (! $input->isValid()) {
                throw new InvalidArgumentException(sprintf(
                    '"%s" is not a valid value for %s',
                    is_string($attrValue) ? $attrValue : json_encode($attrValue),
                    $attrName
                ));
            }
        }

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
    /**
     * Specifically for use cases to update map attributes passing in counterService.deadline etc
     * @psalm-suppress ArgumentTypeCoercion
     */
    public function updateCaseChildAttribute(string $uuid, string $attrName, string $attrType, mixed $attrValue): void
    {
        $attributes = explode(".", $attrName);

        $caseSubClass = '\\' . ucfirst($attributes[0]);

        $fqnClass = "Application\Model\Entity" . $caseSubClass;

        if (! property_exists($fqnClass, $attributes[1])) {
            throw new InvalidArgumentException(
                sprintf($caseSubClass . ' has no such property "%s"', $attributes[1])
            );
        }
        $inputFilter = (new AttributeBuilder())
            ->createForm($fqnClass)
            ->getInputFilter();
        $input = $inputFilter->get($attributes[1]);

        if ($input instanceof InputInterface) {
            $input->setValue($attrValue);

            if (! $input->isValid()) {
                throw new InvalidArgumentException(sprintf(
                    '"%s" is not a valid value for %s',
                    is_string($attrValue) ? $attrValue : json_encode($attrValue),
                    $attributes[1]
                ));
            }
        }

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
                'UpdateExpression' => "set #CV.#NV=:NV",
                'ExpressionAttributeNames' => [
                    '#CV' => $attributes[0],
                    '#NV' => $attributes[1],
                ],
                'ExpressionAttributeValues' => [
                    ':NV' => [
                        $attrType => $attrValue
                    ]
                ],
            ]);
        } catch (AwsException $e) {
            $this->logger->error('Unable to update attributes [' . $e->getMessage() . '] for case' . $uuid, [
                'data' => [$attrName => $attrValue]
            ]);
        }
    }
}
