<?php

declare(strict_types=1);


namespace ApplicationTest\Helpers;
use Application\Enums\LpaTypes;
use Application\Enums\LpaActorTypes;
use Application\Helpers\AddDonorFormHelper;
use PHPUnit\Framework\TestCase;

class AddDonorFormHelperTest extends TestCase
{

    private static array $baseLpa = [

    ];

    private static array $baseDetailsData = [

    ];

    /**
     * @dataProvider addressData
     */
    function testcheckLpa(array $lpa, array $detailsData, array $expectedResponse): void
    {
        $addDonorFormHelper = new AddDonorFormHelper();
        $response = $addDonorFormHelper->checkLpa($lpa, $detailsData);
        $this->assertEquals($expectedResponse, $response);
    }
}
