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
    public function testFindLpa(
        array $depData,
        array $expected
    ): void {
        $dependendcyCheck = new DependencyCheck($depData);

        $this->assertEquals($expected, $dependendcyCheck->getProcessedStatus());
    }


    public static function statusData(): array
    {
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
                    IdMethod::DrivingLicenseNumber->value => true,
                    IdMethod::PassportNumber->value => true,
                    IdMethod::NationalInsuranceNumber->value => true,
                    IdMethod::PostOffice->value => true,
                    'EXPERIAN' => true
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
                    IdMethod::DrivingLicenseNumber->value => false,
                    IdMethod::PassportNumber->value => false,
                    IdMethod::NationalInsuranceNumber->value => false,
                    IdMethod::PostOffice->value => true,
                    'EXPERIAN' => false
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
                    IdMethod::DrivingLicenseNumber->value => false,
                    IdMethod::PassportNumber->value => false,
                    IdMethod::NationalInsuranceNumber->value => false,
                    IdMethod::PostOffice->value => true,
                    'EXPERIAN' => false
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
                    IdMethod::DrivingLicenseNumber->value => true,
                    IdMethod::PassportNumber->value => false,
                    IdMethod::NationalInsuranceNumber->value => true,
                    IdMethod::PostOffice->value => true,
                    'EXPERIAN' => true
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
                    IdMethod::DrivingLicenseNumber->value => true,
                    IdMethod::PassportNumber->value => true,
                    IdMethod::NationalInsuranceNumber->value => true,
                    IdMethod::PostOffice->value => false,
                    'EXPERIAN' => true
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
                    IdMethod::DrivingLicenseNumber->value => false,
                    IdMethod::PassportNumber->value => false,
                    IdMethod::NationalInsuranceNumber->value => false,
                    IdMethod::PostOffice->value => false,
                    'EXPERIAN' => false
                ],
            ],
        ];
    }
}
