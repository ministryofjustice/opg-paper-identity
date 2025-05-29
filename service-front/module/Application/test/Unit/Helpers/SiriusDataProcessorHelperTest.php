<?php

declare(strict_types=1);

namespace ApplicationTest\Unit\Helpers;

use Application\Contracts\OpgApiServiceInterface;
use Application\Enums\PersonType;
use Application\Exceptions\HttpException;
use Application\Exceptions\LpaNotFoundException;
use Application\Helpers\AddressProcessorHelper;
use Application\Helpers\SiriusDataProcessorHelper;
use Application\Services\SiriusApiService;
use Laminas\Http\Request;
use OpenTelemetry\API\Instrumentation\Configuration\General\PeerConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SiriusDataProcessorHelperTest extends TestCase
{
    private OpgApiServiceInterface&MockObject $opgApiServiceMock;
    private SiriusApiService&MockObject $siriusApiServiceMock;
    private SiriusDataProcessorHelper $helper;

    protected function setUp(): void
    {
        $this->opgApiServiceMock = $this->createMock(OpgApiServiceInterface::class);
        $this->siriusApiServiceMock = $this->createMock(SiriusApiService::class);

        $this->helper = new SiriusDataProcessorHelper(
            $this->opgApiServiceMock,
            $this->siriusApiServiceMock
        );
    }

    public function testCreatePaperIdCase(): void
    {
        $type = PersonType::Donor;
        $lpasQuery = ['caseId' => '12345'];
        $lpaData = [
            'opg.poas.lpastore' => [
                'donor' => [
                    'firstNames' => 'Rocky',
                    'lastName' => 'Balboa',
                    'dateOfBirth' => '1980-01-01',
                    'address' => [
                        'line1' => '123 Main St',
                        'town' => 'Test town',
                        'postcode' => 'AB12 3CD',
                        'country' => 'UK',
                    ],
                ],
                'attorneys' => [
                    [
                        'firstNames' => 'Apollo',
                        'lastName' => 'Creed',
                        'dateOfBirth' => '1975-05-05',
                    ],
                ],
                'status' => 'in-progress',
                'certificateProvider' => [
                    'firstNames' => 'Mickey',
                    'lastName' => 'Goldmill',
                    'dateOfBirth' => '1940-02-15',
                    'address' => [
                        'line1' => '456 Boxing St',
                        'town' => 'Philadelphia',
                        'postcode' => 'CD45 6EF',
                        'country' => 'USA',
                    ],
                ],
                'lpaType' => 'property-and-financial',
            ],
            'opg.poas.sirius' => [
                'caseSubtype' => 'some-subtype',
                'donor' => [
                    'addressLine1' => '123 Main St',
                    'addressLine2' => 'Suite 10',
                    'addressLine3' => '',
                    'country' => 'UK',
                    'dob' => '1980-01-01',
                    'firstname' => 'Rocky',
                    'postcode' => 'AB12 3CD',
                    'surname' => 'Balboa',
                    'town' => 'Test town',
                ],
                'id' => 123,
                'uId' => 'M-0000-0000-0000',
            ],
        ];


        $processedData = [
            'first_name' => 'Rocky',
            'last_name' => 'Balboa',
            'dob' => '1980-01-01',
            'address' => [
                'line1' => '123 Main St',
                'line2' => '',
                'line3' => '',
                'town' => 'Test town',
                'postcode' => 'AB12 3CD',
                'country' => 'UK'
            ]
        ];

        $this->opgApiServiceMock
            ->expects(self::once())
            ->method('createCase')
            ->with(
                $processedData['first_name'],
                $processedData['last_name'],
                $processedData['dob'],
                $type,
                $lpasQuery,
                $processedData['address']
            )
            ->willReturn(['caseId' => '67890']);

        $result = $this->helper->createPaperIdCase($type, $lpasQuery, $lpaData);

        $this->assertEquals(['caseId' => '67890'], $result);
    }

    public function testUpdatePaperIdCaseFromSirius(): void
    {
        $uuid = 'abcd-1234-abcd-1234-abcd-1234';
        $request = $this->createMock(Request::class);
        $detailsData = [
            'lpas' => ['LPA123'],
            'personType' => PersonType::Donor,
            'firstName' => 'Jane',
            'lastName' => 'Smithe',
            'dob' => '1974-12-31',
            'address' => [
                'line1' => '457 High St',
                'town' => 'Test City',
                'postcode' => 'CD45 6EF',
                'country' => 'UK'
            ]
        ];
        $lpaData = [
            'opg.poas.lpastore' => [
                'donor' => [
                    'firstNames' => 'Jane',
                    'lastName' => 'Smith',
                    'dateOfBirth' => '1975-12-31',
                    'address' => [
                        'line1' => '456 High St',
                        'town' => 'Test City',
                        'postcode' => 'CD45 6EF',
                        'country' => 'UK'
                    ]
                ]
            ]
        ];
        $processedData = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'dob' => '1975-12-31',
            'address' => [
                'line1' => '456 High St',
                'line2' => '',
                'line3' => '',
                'town' => 'Test City',
                'postcode' => 'CD45 6EF',
                'country' => 'UK'
            ]
        ];

        $this->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($uuid)
            ->willReturn($detailsData);

        $this->siriusApiServiceMock
            ->expects(self::once())
            ->method('getLpaByUid')
            ->with('LPA123', $request)
            ->willReturn($lpaData);

        $this->opgApiServiceMock
            ->expects(self::once())
            ->method('updateCaseSetName')
            ->with($uuid, $processedData['first_name'], $processedData['last_name']);

        $this->opgApiServiceMock
            ->expects(self::once())
            ->method('updateCaseSetDob')
            ->with($uuid, $processedData['dob']);

        $this->opgApiServiceMock
            ->expects(self::once())
            ->method('updateCaseAddress')
            ->with($uuid, $processedData['address']);

        $this->helper->updatePaperIdCaseFromSirius($uuid, $request);
    }

    public function testUpdatePaperIdCaseFromSiriusThrowsExceptionWhenLpaNotFound(): void
    {
        $uuid = 'abcd-1234-abcd-1234-abcd-1234';
        $request = $this->createMock(Request::class);
        $detailsData = [
            'lpas' => ['LPA123'],
            'personType' => PersonType::Donor
        ];

        $this->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($uuid)
            ->willReturn($detailsData);

        $this->siriusApiServiceMock
            ->expects(self::once())
            ->method('getLpaByUid')
            ->with('LPA123', $request)
            ->willReturn(null);

        $this->expectException(LpaNotFoundException::class);
        $this->expectExceptionMessage('LPA not found: LPA123');

        $this->helper->updatePaperIdCaseFromSirius($uuid, $request);
    }

    public function testUpdatePaperIdCaseFromSiriusDoesntUpdateUnchangedOrNullValues(): void
    {
        $uuid = 'abcd-1234-abcd-1234-abcd-1234';
        $request = $this->createMock(Request::class);
        $detailsData = [
            'lpas' => ['LPA123'],
            'personType' => PersonType::CertificateProvider,
            'firstName' => 'Jane',
            'lastName' => 'Smith',
            'dob' => null,
            'address' => [
                'line1' => '456 High St',
                'line2' => '',
                'line3' => '',
                'town' => 'Test City',
                'postcode' => 'CD45 6EF',
                'country' => 'UK'
            ]
        ];
        $lpaData = [
            'opg.poas.lpastore' => [
                'certificateProvider' => [
                    'firstNames' => 'Jane',
                    'lastName' => 'Smith',
                    'address' => [
                        'line1' => '456 High St',
                        'town' => 'Test City',
                        'postcode' => 'CD45 6EF',
                        'country' => 'UK'
                    ]
                ]
            ]
        ];

        $this->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($uuid)
            ->willReturn($detailsData);

        $this->siriusApiServiceMock
            ->expects(self::once())
            ->method('getLpaByUid')
            ->with('LPA123', $request)
            ->willReturn($lpaData);

        $this->opgApiServiceMock
            ->expects($this->never())
            ->method('updateCaseSetName');

        $this->opgApiServiceMock
            ->expects($this->never())
            ->method('updateCaseSetDob');

        $this->opgApiServiceMock
            ->expects($this->never())
            ->method('updateCaseAddress');

        $this->helper->updatePaperIdCaseFromSirius($uuid, $request);
    }

    public function testProcessLpaResponse(): void
    {
        $type = PersonType::Donor;
        $data = [
            'opg.poas.lpastore' => [
                'donor' => [
                    'firstNames' => 'Alice',
                    'lastName' => 'Brown',
                    'dateOfBirth' => '1990-07-15',
                    'address' => [
                        'line1' => '789 Elm St',
                        'line2' => 'Suite 10',
                        'line3' => '',
                        'town' => 'Smalltown',
                        'postcode' => 'EF67 8GH',
                        'country' => 'UK'
                    ]
                ]
            ]
        ];

        $expected = [
            'first_name' => 'Alice',
            'last_name' => 'Brown',
            'dob' => '1990-07-15',
            'address' => [
                'line1' => '789 Elm St',
                'line2' => 'Suite 10',
                'line3' => '',
                'town' => 'Smalltown',
                'postcode' => 'EF67 8GH',
                'country' => 'UK'
            ]
        ];

        $result = $this->helper->processLpaResponse($type, $data);

        $this->assertEquals($expected, $result);
    }

    public function testProcessAddress(): void
    {
        $address = [
            'addressLine1' => '123 Main Street',
            'addressLine2' => 'Apartment 4B',
            'town' => 'Test town',
            'postcode' => 'AB12 3CD',
            'country' => 'UK'
        ];

        $expected = [
            'line1' => '123 Main Street',
            'line2' => 'Apartment 4B',
            'line3' => '',
            'town' => 'Test town',
            'postcode' => 'AB12 3CD',
            'country' => 'UK'
        ];

        $response = AddressProcessorHelper::processAddress($address, 'siriusAddressType');

        $this->assertEquals($expected, $response);
    }
}
