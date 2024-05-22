<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Entity;

use Application\Model\Entity\CaseData;
use Laminas\Form\Annotation\AttributeBuilder;
use PHPUnit\Framework\TestCase;

class CaseDataTest extends TestCase
{
    /**
     * @dataProvider isValidDataProvider
     */
    public function testIsValid(array $data, bool $expectedIsValidResult): void
    {
        $requestData = CaseData::fromArray($data);

        $validator = (new AttributeBuilder())
            ->createForm($requestData)
            ->setData(get_object_vars($requestData));

        $this->assertEquals($validator->isValid(), $expectedIsValidResult);
    }

    public static function isValidDataProvider(): array
    {
        $validData = [
            'firstName' => 'first',
            'lastName' => 'last',
            'personType' => 'donor',
            'dob'   => '1966-10-10',
            'lpas' => [
                'M-AGAS-YAGA-35G3',
                'M-VGAS-OAGA-34G9'
            ],
            'address' => [
                'address line 1',
                'country',
                'post code'
            ]
        ];

        return [
            [$validData, true],
            [array_merge($validData, ['lpas' => ['M-AGGA-XX']]), false],
            [array_merge($validData, ['lastName' => '']), false],
            [array_merge($validData, ['dob' => '11-11-2020']), false],
            [array_replace_recursive($validData, ['lpas' => ['xx']]), false],
        ];
    }
}
