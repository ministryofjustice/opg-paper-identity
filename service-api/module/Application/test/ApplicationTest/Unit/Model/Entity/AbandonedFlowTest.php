<?php

declare(strict_types=1);

namespace ApplicationTest\Unit\Model\Entity;

use Application\Model\Entity\AbandonedFlow;
use Laminas\Form\Annotation\AttributeBuilder;
use PHPUnit\Framework\TestCase;

class AbandonedFlowTest extends TestCase
{
    public function testValidInput(): void
    {
        $data = $this->getData();

        $abandonedFlow = AbandonedFlow::fromArray($data);

        $this->assertEquals($data['last_page'], $abandonedFlow->last_page);
        $this->assertEquals($data['timestamp'], $abandonedFlow->timestamp);
    }

    /**
     * @dataProvider invalidDataProvider
     */
    public function testFormValidation(array $data, bool $isValid): void
    {
        $form = (new AttributeBuilder())->createForm(AbandonedFlow::class);

        $form->setData($data);
        $this->assertEquals($isValid, $form->isValid());
    }

    public static function invalidDataProvider(): array
    {
        return [
            'valid' => [self::getData(), true],
            'missing last_page' => [self::getData(lastPage: null), true],
            'empty last_page' => [self::getData(lastPage: ''), false],
            'missing timestamp' => [self::getData(timestamp: null), false],
        ];
    }

    public static function getData(
        ?string $lastPage = 'page',
        ?string $timestamp = '2021-01-01'
    ): array {
        return [
            'last_page' => $lastPage,
            'timestamp' => $timestamp,
        ];
    }
}
