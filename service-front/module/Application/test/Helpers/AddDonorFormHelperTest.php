<?php

declare(strict_types=1);


namespace ApplicationTest\Helpers;
use Application\Enums\LpaTypes;
use Application\Enums\LpaActorTypes;
use Application\Helpers\AddDonorFormHelper;
use Application\Helpers\VoucherMatchLpaActorHelper;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class AddDonorFormHelperTest extends TestCase
{

    private VoucherMatchLpaActorHelper&MockObject $matchHelperMock;
    private AddDonorFormHelper $addDonorFormHelper;

    public function setUp(): void
    {
        $this->matchHelperMock = $this->createMock(VoucherMatchLpaActorHelper::class);

        parent::setUp();

        $this->addDonorFormHelper = new AddDonorFormHelper($this->matchHelperMock);
    }

    public static array $baseLpa = [
        'opg.poas.sirius' => [
            'donor' => [
                'firstname' => 'First name',
                'surname' => 'LastName',
                'dob' => '10/02/1990',
                'addressLine1' => '123 Fakestreet',
                'town' => 'faketown',
                'postcode' => 'FA2 3KE'
            ]
        ]
    ];

    private static array $baseDetailsData = [
        'firstName' => 'Voucher',
        'lastName' => 'McVoucher',
        'dob' => '1990-11-15',
        'address' => [
            'line1' => '321 Pretend Road',
            'town' => 'NotReal',
            'postcode' => 'NR9 3PR'
        ],
        'lpas' => ['M-0000-0000-0000'],
    ];

    function testGetDonorNameFromSiriusResponse(): void
    {
        $response = $this->addDonorFormHelper->getDonorNameFromSiriusResponse(self::$baseLpa);
        $this->assertEquals('First name LastName', $response);
    }

    function testGetDonorDobFromSiriusResponse(): void
    {
        $response = $this->addDonorFormHelper->getDonorDobFromSiriusResponse(self::$baseLpa);
        $this->assertEquals('10 Feb 1990', $response);
    }

    /**
     * @dataProvider statusData
     */
    function testCheckLpaStatus($lpaStoreData, $expectedResult): void
    {
        $lpaData = self::$baseLpa;
        $lpaData['opg.poas.lpastore'] = $lpaStoreData;
        $response = $this->addDonorFormHelper->checkLpaStatus($lpaData);
        $this->assertEquals($expectedResult, $response);
    }

    static function statusData(): array
    {
        return [
            [[], ['problem' => true, 'status' => '', 'message' => 'No LPA Found.']],
            [['status' => 'complete'], ['problem' => true, 'status' => 'complete', 'message' => 'This LPA cannot be added as an ID check has already been completed for this LPA.']],
            [['status' => 'draft'], ['problem' => true, 'status' => 'draft', 'message' => 'This LPA cannot be added as it’s status is set to Draft. LPAs need to be in the In Progress status to be added to this ID check.']],
            [['status' => 'In progress'], ['problem' => false, 'status' => 'In progress', 'message' => '']]
        ];
    }

    /**
     * @dataProvider idMatchData
     */
    function testCheckLpaIdMatch($checkMatchReturn, $checkAddressReturn, $compareNameReturn, $lpastore, $expectedResponse): void
    {
        $this
            ->matchHelperMock
            ->expects(self::once())
            ->method('checkMatch')
            // ->with($this->uuid)
            ->willReturn($checkMatchReturn);

        if (! is_null($checkAddressReturn)) {
            $this
                ->matchHelperMock
                ->expects(self::once())
                ->method('checkAddressDonorMatch')
                ->willReturn($checkAddressReturn);
        }

        if (! is_null($compareNameReturn)) {
            $this
                ->matchHelperMock
                ->expects(self::once())
                ->method('compareName')
                ->willReturn($compareNameReturn);
        }

        $lpa = self::$baseLpa;
        if (! is_null($lpastore)) {
            $lpa['opg.poas.lpastore'] = $lpastore;
        }

        $response = $this->addDonorFormHelper->checkLpaIdMatch($lpa, self::$baseDetailsData);
        $this->assertEquals($expectedResponse, $response);

    }

    static function idMatchData(): array
    {
        $emptyResponse = [
            "problem" => false,
            "error" => false,
            "warning" => "",
            "message" => "",
            "additionalRows" => [],
        ];

        $nameDobMatchDonor = [
            'firstName' => 'Matchfirst',
            'lastName' => 'MatchLast',
            'dob' => '1990-10-11',
            'type' => LpaActorTypes::DONOR->value,
        ];

        $nameDobMatchDonorResponse = array_merge($emptyResponse, [
            'error' => true,
            'message' => 'The person vouching cannot have the same name and date of birth as the donor.',
            'warning' => 'donor-match'
        ]);

        $nameDobMatchAttorney = [
            'firstName' => 'Matchfirst',
            'lastName' => 'MatchLast',
            'dob' => '1990-10-11',
            'type' => LpaActorTypes::ATTORNEY->value,
        ];

        $nameDobMatchAttorneyResponse = array_merge($emptyResponse, [
            'error' => true,
            'message' => 'The person vouching cannot have the same name and date of birth as an attorney.',
            'warning' => 'actor-match',
            'additionalRows' => [
                [
                    'type' => 'Attorney name',
                    'value' => 'Matchfirst MatchLast'
                ],
                [
                    'type' => 'Attorney date of birth',
                    'value' => '11 Oct 1990'
                ]
            ]
        ]);

        $addressMatchDonorResponse = array_merge($emptyResponse, [
            'error' => true,
            'message' => 'The person vouching cannot live at the same address as the donor.',
            'warning' => 'address-match'
        ]);

        $nameMatchCpLpaStore = [
            'certificateProvider' => [
                'firstNames' => 'CPName',
                'lastName' => 'CPSurname'
            ]
            ];

        $nameMatchCpResponse = array_merge($emptyResponse, [
            'message' => 'There is a certificate provider called CPName CPSurname named on this LPA. A certificate provider cannot vouch for the identity of a donor. Confirm that these are two different people with the same name.',
            'warning' => 'actor-match',
            'additionalRows' => [
                [
                    'type' => 'Certificate provider name',
                    'value' => 'CPName CPSurname'
                ]
            ]
        ]);

        return [
            // no matches
            [false, false, false, null, $emptyResponse],
            // match on name and dob with DONOR
            [$nameDobMatchDonor, null, null, null, $nameDobMatchDonorResponse],
            // match on name and dob with ATTORNEY
            [$nameDobMatchAttorney, null, null, null, $nameDobMatchAttorneyResponse],
            // match on address with DONOR
            [false, true, null, null, $addressMatchDonorResponse],
            // match on name with CP
            [false, false, true, $nameMatchCpLpaStore, $nameMatchCpResponse],
        ];
    }

    /**
     * @dataProvider processLpasData
     */
    function testProcessLpas($lpasData, $checkLpaStatusReturns, $checkLpaIdMatchReturns, $expectedResponse): void
    {
        $helper = $this->getMockBuilder('Application\Helpers\AddDonorFormHelper')
            ->setConstructorArgs([new VoucherMatchLpaActorHelper])
            ->onlyMethods(array('checkLpaStatus', 'checkLpaIdMatch'))
            ->getMock();

        if (! is_null($checkLpaStatusReturns)) {
            $helper
                ->expects($this->exactly(count($checkLpaStatusReturns)))
                ->method('checkLpaStatus')
                ->willReturnOnConsecutiveCalls(...$checkLpaStatusReturns);
        }

        if (! is_null($checkLpaIdMatchReturns)) {
            $helper
                ->expects($this->exactly(count($checkLpaIdMatchReturns)))
                ->method('checkLpaIdMatch')
                ->willReturnOnConsecutiveCalls(...$checkLpaIdMatchReturns);
        }

        $response = $helper->processLpas($lpasData, self::$baseDetailsData);
        $this->assertEquals($expectedResponse, $response);
    }

    static function processLpasData(): array
    {
        $baseResponse = [
            'lpasCount' => 0,
            'problem' => false,
            'error' => false,
            'warning' => '',
            'message' => '',
            'additionalRows' => [],
        ];

        $baseCheckLpaStatusResponse = [
            'problem' => false,
            'status' => "In progress",
            'message' => ""
        ];

        $baseCheckLpaIdMatchResponse = [
            "problem" => false,
            "error" => false,
            "warning" => "",
            "message" => "",
            "additionalRows" => [],
        ];

        $noLpaResopnse = array_merge($baseResponse, [
            'problem' => true,
            'message' => 'No LPA Found.'
        ]);

        $lpaOnIdCheck = self::$baseLpa;
        $lpaOnIdCheck['opg.poas.sirius']['uId'] = 'M-0000-0000-0000';

        $lpaOnIdCheckResponse = array_merge($baseResponse, [
            'problem' => true,
            'message' => 'This LPA has already been added to this identity check.'
        ]);

        $lpaOne = self::$baseLpa;
        $lpaOne['opg.poas.sirius']['uId'] = 'M-1111-1111-1111';
        $lpaOne['opg.poas.sirius']['caseSubtype'] = 'personal-welfare';
        $lpaOne['opg.poas.lpastore'] = ['status' => 'In progress'];

        $lpaTwo = self::$baseLpa;
        $lpaTwo['opg.poas.sirius']['uId'] = 'M-2222-2222-2222';
        $lpaTwo['opg.poas.sirius']['caseSubtype'] = 'property-and-affairs';
        $lpaTwo['opg.poas.lpastore'] = ['status' => 'In progress'];

        $happyPathResponse = array_merge($baseResponse, [
            "lpasCount" => 2,
            'donorName' => 'First name LastName',
            'donorDob' => '10 Feb 1990',
            'donorAddress' => [
                'line1' => '123 Fakestreet',
                'line2' => '',
                'line3' => '',
                'town' => 'faketown',
                'postcode' => 'FA2 3KE',
                'country' => ''
            ],
            "lpas" => [
                array_merge($baseCheckLpaIdMatchResponse, [
                    'uId' => 'M-1111-1111-1111',
                    'type' => 'PW'
                ]),
                array_merge($baseCheckLpaIdMatchResponse, [
                    'uId' => 'M-2222-2222-2222',
                    'type' => 'PA'
                ]),
            ],
        ]);

        return [
            // no LPAs
            [[], null, null, $noLpaResopnse],
            // lpa already on Id Check
            [[$lpaOnIdCheck], null, null, $lpaOnIdCheckResponse],
            // happy path
            [
                [$lpaOne, $lpaTwo],
                [$baseCheckLpaStatusResponse, $baseCheckLpaStatusResponse],
                [$baseCheckLpaIdMatchResponse, $baseCheckLpaIdMatchResponse],
                $happyPathResponse,
            ],
        ];
    }
}