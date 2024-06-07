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
        $validator = (new AttributeBuilder())
            ->createForm(CaseData::class)
            ->setData($data);

        if ($expectedIsValidResult) {
            $this->assertTrue(
                $validator->isValid(),
                'Data provided is not valid: ' . json_encode($validator->getMessages())
            );
        } else {
            $this->assertFalse($validator->isValid());
        }
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
            [array_merge($validData, ['documentComplete' => true]), true],
            [array_merge($validData, ['documentComplete' => false]), true],
            [array_merge($validData, ['documentComplete' => 'grergiro']), false],
        ];
    }
}
