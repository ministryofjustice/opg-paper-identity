<?php

declare(strict_types=1);

namespace Application\Fixtures;

use Application\Model\Entity\CaseData;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Aws\Exception\AwsException;
use InvalidArgumentException;
use Laminas\Form\Annotation\AttributeBuilder;
use Laminas\InputFilter\InputFilterInterface;
use Laminas\InputFilter\InputInterface;
use Psr\Log\LoggerInterface;

class DataWriteHandler
{
    public function __construct(
        private readonly DynamoDbClient $dynamoDbClient,
        private readonly string $tableName,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function insertUpdateData(CaseData $item): void
    {
        $marsheler = new Marshaler();
        $encoded = $marsheler->marshalItem($item->jsonSerialize());
        $params = [
            'TableName' => $this->tableName,
            'Item' => $encoded,
        ];

        try {
            $this->dynamoDbClient->putItem($params);
        } catch (AwsException $e) {
            $this->logger->error('Unable to save data [' . $e->getMessage() . '] to ' . $this->tableName, [
                'data' => $item,
            ]);
        }
    }

    public function updateCaseData(string $uuid, string $attrName, mixed $attrValue): void
    {
        $attributeChain = explode(".", $attrName);

        if (! property_exists(CaseData::class, $attributeChain[0])) {
            throw new InvalidArgumentException(sprintf('CaseData has no such property "%s"', $attributeChain[0]));
        }

        $inputFilter = (new AttributeBuilder())
            ->createForm(CaseData::class)
            ->getInputFilter();

        $input = $inputFilter->get($attributeChain[0]);
        foreach (array_slice($attributeChain, 1) as $subAttr) {
            if ($input instanceof InputFilterInterface) {
                $input = $input->get($subAttr);
            } else {
                throw new InvalidArgumentException(
                    sprintf('%s has no such property "%s"', $input->getName(), $subAttr)
                );
            }
        }

        if ($input instanceof InputInterface) {
            $input->setValue($attrValue);

            if (! $input->isValid()) {
                throw new InvalidArgumentException(sprintf(
                    '"%s" is not a valid value for %s',
                    is_string($attrValue) ? $attrValue : json_encode($attrValue, JSON_THROW_ON_ERROR),
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

        $marshaled = (new Marshaler())->marshalValue($attrValue);

        $expressionAttributeNames = [];
        foreach ($attributeChain as $i => $attr) {
            $expressionAttributeNames['#AT' . $i] = $attr;
        }

        try {
            $this->dynamoDbClient->updateItem([
                'Key' => $idKey['key'],
                'TableName' => $this->tableName,
                'UpdateExpression' => "set " . implode('.', array_keys($expressionAttributeNames)) . "=:NV",
                'ExpressionAttributeNames' => $expressionAttributeNames,
                'ExpressionAttributeValues' => [
                    ':NV' => $marshaled,
                ],
            ]);
        } catch (AwsException $e) {
            $this->logger->error('Unable to update data [' . $e->getMessage() . '] for case' . $uuid, [
                'data' => [$attrName => $attrValue],
            ]);
        }
    }
}
