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
    private OpgApiService|MockObject $opgApiService;

    private string $uuid;

    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @dataProvider lpaData
     */
    public function testFindLpa(
        array         $responseData,
        Parameters    $formData,
        FormInterface $form,
        ViewModel     $view,
        array         $templates = [],

    ): void
    {
        $opgApiServiceMock = $this->createMock(OpgApiService::class);
        $formProcessorService = new FormProcessorService($opgApiServiceMock);


        $opgApiServiceMock
            ->expects(self::once())
            ->method('findLpa')
            ->with($responseData['data']['case_uuid'], $responseData['data']['LPA_Number'])
            ->willReturn($responseData);

        $view = $formProcessorService->findLpa($responseData['data']['case_uuid'], $formData, $form, $view, $templates);
        $this->assertTrue(true);
        $this->assertInstanceOf(ViewModel::class, $view);
    }


    public static function lpaData(): array
    {
        $caseUuid = "9130a21e-6e5e-4a30-8b27-76d21b747e60";
        $lpa =  "M-0000-0000-0000";

        $mockResponseData = [
            "data" => [
                "case_uuid" => $caseUuid,
                "LPA_Number" => $lpa,
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
        $view = new ViewModel();
        $templates = [
            'default' => 'application/pages/cp/add_lpa',
        ];
        return [
            [
                $mockResponseData,
                $params,
                $form,
                $view,
                $templates
            ],
        ];
    }
}
