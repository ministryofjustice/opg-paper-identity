<?php

declare(strict_types=1);

namespace ApplicationTest\Helpers;

use Application\Helpers\DependencyCheck;
use Application\Enums\IdMethod;
use Laminas\Form\Annotation\AttributeBuilder;
use Laminas\Form\FormInterface;
use Laminas\Stdlib\Parameters;
use PHPUnit\Framework\TestCase;

use function Aws\flatmap;

class DependencyCheckHelperTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @dataProvider statusData
     */
    public function testDependencyStatus(
        array $depData,
        array $expected
    ): void {
        $dependendcyCheck = new DependencyCheck($depData);

        $this->assertEquals($expected, $dependendcyCheck->getProcessedStatus());
    }


    public static function statusData(): array
    {
        $messagePart = "Some identity verification methods are not presently available";
        $messageAll = "Online identity verification it not presently available";


        return [
            [
                [
                    IdMethod::DrivingLicenseNumber->value => true,
                    IdMethod::PassportNumber->value => true,
                    IdMethod::NationalInsuranceNumber->value => true,
                    IdMethod::PostOffice->value => true,
                    'EXPERIAN' => true
                ],
                [
                    "data" => [
                        IdMethod::DrivingLicenseNumber->value => true,
                        IdMethod::PassportNumber->value => true,
                        IdMethod::NationalInsuranceNumber->value => true,
                        IdMethod::PostOffice->value => true,
                        'EXPERIAN' => true
                    ],
                    "message" => ""
                ],
            ],
            [
                [
                    IdMethod::DrivingLicenseNumber->value => true,
                    IdMethod::PassportNumber->value => true,
                    IdMethod::NationalInsuranceNumber->value => true,
                    IdMethod::PostOffice->value => true,
                    'EXPERIAN' => false
                ],
                [

                    "data" => [
                        IdMethod::DrivingLicenseNumber->value => false,
                        IdMethod::PassportNumber->value => false,
                        IdMethod::NationalInsuranceNumber->value => false,
                        IdMethod::PostOffice->value => true,
                        'EXPERIAN' => false
                    ],
                    "message" => $messageAll
                ],
            ],
            [
                [
                    IdMethod::DrivingLicenseNumber->value => false,
                    IdMethod::PassportNumber->value => false,
                    IdMethod::NationalInsuranceNumber->value => false,
                    IdMethod::PostOffice->value => true,
                    'EXPERIAN' => true
                ],
                [
                    "data" => [
                        IdMethod::DrivingLicenseNumber->value => false,
                        IdMethod::PassportNumber->value => false,
                        IdMethod::NationalInsuranceNumber->value => false,
                        IdMethod::PostOffice->value => true,
                        'EXPERIAN' => false
                    ],
                    "message" => $messageAll
                ],
            ],
            [
                [
                    IdMethod::DrivingLicenseNumber->value => true,
                    IdMethod::PassportNumber->value => false,
                    IdMethod::NationalInsuranceNumber->value => true,
                    IdMethod::PostOffice->value => true,
                    'EXPERIAN' => true
                ],
                [
                    "data" => [
                        IdMethod::DrivingLicenseNumber->value => true,
                        IdMethod::PassportNumber->value => false,
                        IdMethod::NationalInsuranceNumber->value => true,
                        IdMethod::PostOffice->value => true,
                        'EXPERIAN' => true
                    ],
                    "message" => $messagePart
                ],
            ],
            [
                [
                    IdMethod::DrivingLicenseNumber->value => true,
                    IdMethod::PassportNumber->value => true,
                    IdMethod::NationalInsuranceNumber->value => true,
                    IdMethod::PostOffice->value => false,
                    'EXPERIAN' => true
                ],
                [
                    "data" => [
                        IdMethod::DrivingLicenseNumber->value => true,
                        IdMethod::PassportNumber->value => true,
                        IdMethod::NationalInsuranceNumber->value => true,
                        IdMethod::PostOffice->value => false,
                        'EXPERIAN' => true
                    ],
                    "message" => ""
                ],
            ],
            [
                [
                    IdMethod::DrivingLicenseNumber->value => false,
                    IdMethod::PassportNumber->value => false,
                    IdMethod::NationalInsuranceNumber->value => false,
                    IdMethod::PostOffice->value => false,
                    'EXPERIAN' => false
                ],
                [
                    "data" => [
                        IdMethod::DrivingLicenseNumber->value => false,
                        IdMethod::PassportNumber->value => false,
                        IdMethod::NationalInsuranceNumber->value => false,
                        IdMethod::PostOffice->value => false,
                        'EXPERIAN' => false
                    ],
                    "message" => $messageAll
                ],
            ],
        ];
    }
}
