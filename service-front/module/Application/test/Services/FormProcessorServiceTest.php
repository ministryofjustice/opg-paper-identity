<?php

declare(strict_types=1);

namespace ApplicationTest\Services;

use Application\Contracts\OpgApiServiceInterface;
use Application\Exceptions\OpgApiException;
use Application\Forms\LpaReferenceNumber;
use Application\Services\FormProcessorService;
use Application\Services\SiriusApiService;
use Laminas\Form\Annotation\AttributeBuilder;
use Laminas\Form\FormInterface;
use Laminas\Stdlib\ArrayUtils;
use Laminas\Stdlib\Parameters;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use GuzzleHttp\Client;
use Laminas\View\Model\ViewModel;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Application\Services\OpgApiService;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use PHPUnit\Framework\TestCase;
use Laminas\Http\Response as HttpResponse;

class FormProcessorServiceTest extends TestCase
{
//    private OpgApiService|MockObject $opgApiService;

    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @dataProvider lpaData
     */
    public function testFindLpa(
        string $caseUuid,
        string $lpaNumber,
        array $responseData,
        Parameters $formData,
        FormInterface $form,
        array $templates = [],
    ): void {
        $opgApiServiceMock = $this->createMock(OpgApiService::class);
        $formProcessorService = new FormProcessorService($opgApiServiceMock);


        $opgApiServiceMock
            ->expects(self::once())
            ->method('findLpa')
            ->with($caseUuid, $lpaNumber)
            ->willReturn($responseData);

        $processed = $formProcessorService->findLpa($caseUuid, $formData, $form, $templates);
        $this->assertIsArray($processed);
        $this->assertEquals($responseData, $processed['data']);
        $this->assertEquals($caseUuid, $processed['uuid']);
        $this->assertEquals($templates['default'], $processed['template']);
        $this->assertArrayHasKey('lpa_response', $processed['variables']);
        $this->assertEquals($processed['variables']['lpa_response'], $responseData);
    }


    public static function lpaData(): array
    {
        $caseUuid = "9130a21e-6e5e-4a30-8b27-76d21b747e60";
        $goodLpa = "M-0000-0000-0000";
        $alreadyAddedLpa = "M-0000-0000-0001";
        $notFoundLpa = "M-0000-0000-0002";
        $alreadyDoneLpa = "M-0000-0000-0004";
        $draftLpa = "M-0000-0000-0005";
        $onlineLpa = "M-0000-0000-0006";

        $mockResponseData = [
            "data" => [
                "case_uuid" => $caseUuid,
                "LPA_Number" => $goodLpa,
                "Type_Of_LPA" => "Personal welfare",
                "Donor" => "Mary Ann Chapman",
                "Status" => "Processing",
                "CP_Name" => "David Smith",
                "CP_Address" => [
                    "Line_1" => "1082 Penny Street",
                    "Line_2" => "Lancaster",
                    "Town" => "Lancashire",
                    "Postcode" => "LA1 1XN",
                    "Country" => "United Kingdom"
                ]
            ],
            "message" => "Success",
            "status" => 200
        ];

        $form = (new AttributeBuilder())->createForm(LpaReferenceNumber::class);
        $params = new Parameters(['lpa' => $mockResponseData['data']['LPA_Number']]);
        $templates = [
            'default' => 'application/pages/cp/add_lpa',
        ];

        return [
            [
                $caseUuid,
                $goodLpa,
                $mockResponseData,
                $params,
                $form,
                $templates
            ],
            [
                $caseUuid,
                $alreadyAddedLpa,
                [
                    "uuid" => $caseUuid,
                    "message" => "This LPA has already been added to this ID check.",
                    "status" => 400,
                    'data' => [
                        "Status" => "Already added"
                    ]
                ],
                new Parameters(['lpa' => $alreadyAddedLpa]),
                $form,
                $templates
            ]
        ];
    }
}
