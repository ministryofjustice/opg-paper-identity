<?php

declare(strict_types=1);

namespace ApplicationTest\Unit\Services;

use Application\Enums\IdMethod;
use Application\Exceptions\HttpException;
use Application\Exceptions\OpgApiException;
use Application\Services\OpgApiService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Throwable;

class OpgApiServiceTest extends TestCase
{
    /**
     * @dataProvider detailsData
     * @param class-string<Throwable>|null $expectedException
     */
    public function testGetDetailsData(
        Client $client,
        ?array $responseData,
        ?string $expectedException
    ): void {
        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        $opgApiService = new OpgApiService($client);

        $response = $opgApiService->getDetailsData('uuid');

        if ($responseData !== null) {
            $this->assertEquals($responseData, $response);
        }
    }

    public static function detailsData(): array
    {
        $successMockResponseData = [
            "claimedIdentity" => [
                "firstName" => "Mary Ann",
                "lastName" => "Chapman",
                "dob" => "1943-05-01",
                "address" => [
                    'line1' => '1 Street',
                    'line2' => '',
                    'line3' => '',
                    'town' => 'Middleton',
                    'postcode' => 'LA1 2XN',
                    'country' => 'DD',
                ],
            ],
            "personType" => "donor",
            "identityCheckPassed" => true,
        ];

        $expectedReturnData = [
            "firstName" => "Mary Ann",
            "lastName" => "Chapman",
            "dob" => "1943-05-01",
            "address" => [
                'line1' => '1 Street',
                'line2' => '',
                'line3' => '',
                'town' => 'Middleton',
                'postcode' => 'LA1 2XN',
                'country' => 'DD',
            ],
            "professionalAddress" => null,
            "personType" => "donor",
            "identityCheckPassed" => true,
        ];

        $successMock = new MockHandler([
            new Response(200, [], json_encode($successMockResponseData, JSON_THROW_ON_ERROR)),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $failMock = new MockHandler([
            new Response(400, [], json_encode(['Bad Request'], JSON_THROW_ON_ERROR)),
        ]);
        $handlerStack = HandlerStack::create($failMock);
        $failClient = new Client(['handler' => $handlerStack]);

        $notFoundMock = new MockHandler([
            new Response(404, [], json_encode(['error' => 'Case not found'], JSON_THROW_ON_ERROR)),
        ]);
        $handlerStack = HandlerStack::create($notFoundMock);
        $notFoundClient = new Client(['handler' => $handlerStack]);

        $identityCheckExceptionMock = new MockHandler([
            new Response(
                200,
                [],
                json_encode(array_merge(
                    $successMockResponseData,
                    ["identityCheckPassed" => true]
                ), JSON_THROW_ON_ERROR)
            ),
        ]);
        $identityCheckHandler = HandlerStack::create($identityCheckExceptionMock);
        $identityCheckClient = new Client(['handler' => $identityCheckHandler]);

        $identityCheckNullMock = new MockHandler([
            new Response(
                200,
                [],
                json_encode(array_merge(
                    $successMockResponseData,
                    ["identityCheckPassed" => null]
                ), JSON_THROW_ON_ERROR)
            ),
        ]);
        $identityCheckNullHandler = HandlerStack::create($identityCheckNullMock);
        $identityCheckNullClient = new Client(['handler' => $identityCheckNullHandler]);

        $expectedReturnDataNullCheck = [
            "firstName" => "Mary Ann",
            "lastName" => "Chapman",
            "dob" => "1943-05-01",
            "address" => [
                'line1' => '1 Street',
                'line2' => '',
                'line3' => '',
                'town' => 'Middleton',
                'postcode' => 'LA1 2XN',
                'country' => 'DD',
            ],
            "professionalAddress" => null,
            "personType" => "donor",
            "identityCheckPassed" => null,
        ];

        return [
            // Success Case
            [
                $successClient,
                $expectedReturnData,
                null,
                true,
            ],
            // Bad Request Case
            [
                $failClient,
                null,
                OpgApiException::class,
                false,
            ],
            // Not Found Case
            [
                $notFoundClient,
                null,
                HttpException::class,
                false,
            ],
            // Identity Check Passed Null Case
            [
                $identityCheckNullClient,
                $expectedReturnDataNullCheck,
                null,
                false,
            ],
            // Identity Check Passed
            [
                $identityCheckClient,
                $expectedReturnData,
                null,
                false,
            ],
        ];
    }

    /**
     * @dataProvider ninoData
     */
    public function testValidateNino(string $nino, Client $client, array $responseData): void
    {
        $opgApiService = new OpgApiService($client);

        $response = $opgApiService->checkNinoValidity('uuid', $nino);

        $this->assertEquals($responseData['result'], $response);
    }

    public static function ninoData(): array
    {
        $validNino = 'AA112233A';
        $invalidNino = 'AA112233C';

        $successMockResponseData = [
            'result' => 'PASS',
            'nino' => $validNino,
        ];

        $successMock = new MockHandler([
            new Response(
                200,
                ['X-Foo' => 'Bar'],
                json_encode($successMockResponseData, JSON_THROW_ON_ERROR)
            ),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $failMockResponseData = [
            'result' => 'NO_MATCH',
            'nino' => $invalidNino,
        ];
        $failMock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], json_encode($failMockResponseData, JSON_THROW_ON_ERROR)),
        ]);
        $handlerStack = HandlerStack::create($failMock);
        $failClient = new Client(['handler' => $handlerStack]);

        return [
            [
                $validNino,
                $successClient,
                [
                    'result' => 'PASS',
                    'nino' => $validNino
                ]
            ],
            [
                $invalidNino,
                $failClient,
                [
                    'result' => 'NO_MATCH',
                    'nino' => $validNino
                ]
            ]
        ];
    }

    /**
     * @dataProvider dlnData
     */
    public function testValidateDln(string $dln, Client $client, string $responseData, bool $exception): void
    {
        if ($exception) {
            $this->expectException(OpgApiException::class);
        }

        $opgApiService = new OpgApiService($client);

        $response = $opgApiService->checkDlnValidity($dln);

        $this->assertEquals($responseData, $response);
    }

    public static function dlnData(): array
    {
        $validDln = 'CHAPM301534MA9AY';
        $invalidDln = 'JONES710238HA3D8';
        $insufficientDln = 'JONES710238HA3D9';

        $successMockResponseData = [
            'status' => 'PASS',
            'dln' => $validDln,
        ];

        $successMock = new MockHandler([
            new Response(
                200,
                ['X-Foo' => 'Bar'],
                json_encode($successMockResponseData, JSON_THROW_ON_ERROR)
            ),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $failMockResponseData = [
            'status' => 'NO_MATCH',
            'dln' => $invalidDln,
        ];
        $failMock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], json_encode($failMockResponseData, JSON_THROW_ON_ERROR)),
        ]);
        $handlerStack = HandlerStack::create($failMock);
        $failClient = new Client(['handler' => $handlerStack]);

        $insufficientMockResponseData = [
            'status' => 'NOT_ENOUGH_DETAILS',
            'dln' => $insufficientDln,
        ];
        $insufficientMock = new MockHandler([
            new Response(
                200,
                ['X-Foo' => 'Bar'],
                json_encode($insufficientMockResponseData, JSON_THROW_ON_ERROR)
            ),
        ]);
        $handlerStack = HandlerStack::create($insufficientMock);
        $insufficientClient = new Client(['handler' => $handlerStack]);

        return [
            [
                $validDln,
                $successClient,
                'PASS',
                false,
            ],
            [
                $invalidDln,
                $failClient,
                'NO_MATCH',
                false,
            ],
            [
                $insufficientDln,
                $insufficientClient,
                'NOT_ENOUGH_DETAILS',
                false,
            ],
        ];
    }

    /**
     * @dataProvider passportData
     */
    public function testValidatePassport(string $passport, Client $client, string $responseData, bool $exception): void
    {
        if ($exception) {
            $this->expectException(OpgApiException::class);
        }

        $opgApiService = new OpgApiService($client);

        $response = $opgApiService->checkPassportValidity($passport);

        $this->assertEquals($responseData, $response);
    }

    public static function passportData(): array
    {
        $validPassport = '987654321';
        $invalidPassport = '123456789';
        $insufficientPassport = '123456788';

        $successMockResponseData = [
            'status' => 'PASS',
            'passport' => $validPassport,
        ];

        $successMock = new MockHandler([
            new Response(
                200,
                ['X-Foo' => 'Bar'],
                json_encode($successMockResponseData, JSON_THROW_ON_ERROR)
            ),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $failMockResponseData = [
            'status' => 'NO_MATCH',
            'passport' => $invalidPassport,
        ];
        $failMock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], json_encode($failMockResponseData, JSON_THROW_ON_ERROR)),
        ]);
        $handlerStack = HandlerStack::create($failMock);
        $failClient = new Client(['handler' => $handlerStack]);

        $insufficientMockResponseData = [
            'status' => 'NOT_ENOUGH_DETAILS',
            'passport' => $insufficientPassport,
        ];
        $insufficientMock = new MockHandler([
            new Response(
                200,
                ['X-Foo' => 'Bar'],
                json_encode($insufficientMockResponseData, JSON_THROW_ON_ERROR)
            ),
        ]);
        $handlerStack = HandlerStack::create($insufficientMock);
        $insufficientClient = new Client(['handler' => $handlerStack]);

        return [
            [
                $validPassport,
                $successClient,
                'PASS',
                false,
            ],
            [
                $invalidPassport,
                $failClient,
                'NO_MATCH',
                false,
            ],
            [
                $insufficientPassport,
                $insufficientClient,
                'NOT_ENOUGH_DETAILS',
                false,
            ],
        ];
    }

    public function testGetIdCheckQuestions(): void
    {
        $uuid = '49895f88-501b-4491-8381-e8aeeaef177d';

        $mockResponseData = [
            "one" => [
                "externalId" => "Q1",
                "question" => "Who provides your mortgage?",
                "prompts" => [
                    0 => "Nationwide",
                    1 => "Halifax",
                    2 => "Lloyds",
                    3 => "HSBC",
                ],
            ],
            "two" => [
                "externalId" => "Q2",
                "question" => "Who provides your personal mobile contract?",
                "prompts" => [
                    0 => "EE",
                    1 => "Vodafone",
                    2 => "BT",
                    3 => "iMobile",
                ],
            ],
            "three" => [
                "externalId" => "Q3",
                "question" => "What are the first two letters of the last name of another person
                on the electroal register at your address?",
                "prompts" => [
                    0 => "Ka",
                    1 => "Ch",
                    2 => "Jo",
                    3 => "None of the above",
                ],
            ],
            "four" => [
                "externalId" => "Q4",
                "question" => "Who provides your current account?",
                "prompts" => [
                    0 => "Santander",
                    1 => "HSBC",
                    2 => "Halifax",
                    3 => "Nationwide",
                ],
            ],
        ];

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], json_encode($mockResponseData, JSON_THROW_ON_ERROR)),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $opgApiService = new OpgApiService($client);

        $response = $opgApiService->getIdCheckQuestions($uuid);

        $this->assertEquals($mockResponseData, $response);
    }

    /**
     * @dataProvider idCheckData
     * @return void
     */
    public function testCheckIdCheckAnswers(array $answers, Client $client, bool $passed, bool $exception): void
    {
        if ($exception) {
            $this->expectException(OpgApiException::class);
        }
        $uuid = '49895f88-501b-4491-8381-e8aeeaef177d';

        $opgApiService = new OpgApiService($client);

        $response = $opgApiService->checkIdCheckAnswers($uuid, $answers);

        $this->assertEquals(true, $response['complete']);
        $this->assertEquals($passed, $response['passed']);
    }

    public static function idCheckData(): array
    {
        $correctAnswers = [
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
        ];

        $wrongAnswers = [
            1 => 2,
            2 => 0,
            3 => 0,
            4 => 0,
        ];

        $correctResponse = [
            'complete' => true,
            'passed' => true,
        ];

        $failResponse = [
            'complete' => true,
            'passed' => false,
        ];

        $successMock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], json_encode($correctResponse, JSON_THROW_ON_ERROR)),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $failMock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], json_encode($failResponse, JSON_THROW_ON_ERROR)),
        ]);
        $handlerStack = HandlerStack::create($failMock);
        $failClient = new Client(['handler' => $handlerStack]);

        return [
            [
                $correctAnswers,
                $successClient,
                true,
                false,
            ],
            [
                $wrongAnswers,
                $failClient,
                false,
                false,
            ],
        ];
    }

    /**
     * @dataProvider caseData
     */
    public function testGetCaseUuid(array $postData, Client $client, array $responseData, bool $exception): void
    {
        if ($exception) {
            $this->expectException(OpgApiException::class);
        }
        $opgApiService = new OpgApiService($client);

        $response = $opgApiService->createCase(
            $postData['FirstName'],
            $postData['LastName'],
            $postData['DOB'],
            $postData['personType'],
            $postData['lpas'],
            $postData['address'],
        );

        $this->assertEquals($responseData, $response);
    }

    public static function caseData(): array
    {
        $uuid = '49895f88-501b-4491-8381-e8aeeaef177d';
        $firstName = "Mary Anne";
        $lastName = "Chapman";
        $dob = "1943-01-01";
        $lpas = [
            "PA M-XYXY-YAGA-35G3",
            "PW M-VGAS-OAGA-34G9",
        ];

        $postData = [
            "FirstName" => $firstName,
            "LastName" => $lastName,
            'DOB' => $dob,
            'personType' => 'donor',
            "lpas" => $lpas,
            'address' => [
                "Line 1",
                "Town",
                "Country",
                "PostOfficePostcode",
            ],
        ];

        $successMockResponseData = [
            "case_uuid" => $uuid,
            "name" => $postData['FirstName'] . " " . $postData['LastName'],
            "lpas" => $lpas,
        ];
        $successMock = new MockHandler([
            new Response(
                200,
                ['X-Foo' => 'Bar'],
                json_encode($successMockResponseData, JSON_THROW_ON_ERROR)
            ),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $failMockResponseData = ['error' => 'POST /cases/create resulted in a `400 Bad Request`'];
        $failMock = new MockHandler([
            new Response(400, ['X-Foo' => 'Bar'], json_encode($failMockResponseData, JSON_THROW_ON_ERROR)),
        ]);
        $handlerStack = HandlerStack::create($failMock);
        $failClient = new Client(['handler' => $handlerStack]);

        return [
            [
                $postData,
                $successClient,
                $successMockResponseData,
                false,
            ],
            [
                $postData,
                $failClient,
                $failMockResponseData,
                true,
            ],
        ];
    }

    /**
     * @dataProvider updateIdMethodData
     * @return void
     */
    public function testUpdateIdMethod(array $data, Client $client, bool $exception): void
    {
        if ($exception) {
            $this->expectException(OpgApiException::class);
        } else {
            $this->expectNotToPerformAssertions();
        }

        $opgApiService = new OpgApiService($client);

        $opgApiService->updateIdMethod($data['uuid'], $data['method']);
    }

    public static function updateIdMethodData(): array
    {
        $data = [];
        $data['uuid'] = '49895f88-501b-4491-8381-e8aeeaef177d';
        $data['method'] = "nin";

        $successMock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], ''),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $failMock = new MockHandler([
            new Response(400, ['X-Foo' => 'Bar'], ''),
        ]);
        $handlerStack = HandlerStack::create($failMock);
        $failClient = new Client(['handler' => $handlerStack]);

        return [
            [
                $data,
                $successClient,
                false,
            ],
            [
                $data,
                $failClient,
                true,
            ],
        ];
    }

    /**
     * @dataProvider setDocumentCompleteData
     * @return void
     */
    public function testSetDocumentCompleteMethod(
        array $data,
        Client $client,
        bool $exception
    ): void {
        if ($exception) {
            $this->expectException(OpgApiException::class);
        } else {
            $this->expectNotToPerformAssertions();
        }

        $opgApiService = new OpgApiService($client);

        $opgApiService->updateCaseSetDocumentComplete($data['uuid'], IdMethod::NationalInsuranceNumber->value);
    }

    public static function setDocumentCompleteData(): array
    {
        $data = [];
        $data['uuid'] = '49895f88-501b-4491-8381-e8aeeaef177d';

        $successMock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], ''),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $failMock = new MockHandler([
            new Response(400, ['X-Foo' => 'Bar'], ''),
        ]);
        $handlerStack = HandlerStack::create($failMock);
        $failClient = new Client(['handler' => $handlerStack]);

        return [
            [
                $data,
                $successClient,
                false,
            ],
            [
                $data,
                $failClient,
                true,
            ],
        ];
    }


    /**
     * @dataProvider setDobData
     * @return void
     */
    public function testSetDobMethod(
        array $data,
        Client $client,
        bool $exception
    ): void {
        if ($exception) {
            $this->expectException(OpgApiException::class);
        } else {
            $this->expectNotToPerformAssertions();
        }

        $opgApiService = new OpgApiService($client);

        $opgApiService->updateCaseSetDob($data['uuid'], $data['dob']);
    }

    public static function setDobData(): array
    {
        $data = [];
        $data['uuid'] = '49895f88-501b-4491-8381-e8aeeaef177d';
        $data['dob'] = '1980-01-01';

        $successMock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], ''),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $failMock = new MockHandler([
            new Response(400, ['X-Foo' => 'Bar'], ''),
        ]);
        $handlerStack = HandlerStack::create($failMock);
        $failClient = new Client(['handler' => $handlerStack]);

        return [
            [
                $data,
                $successClient,
                false,
            ],
            [
                $data,
                $failClient,
                true,
            ],
        ];
    }


    /**
     * @dataProvider setAbandonCaseData
     * @return void
     */
    public function testAbandonCaseMethod(
        array $data,
        Client $client,
        bool $exception
    ): void {
        if ($exception) {
            $this->expectException(OpgApiException::class);
        } else {
            $this->expectNotToPerformAssertions();
        }

        $opgApiService = new OpgApiService($client);

        $opgApiService->updateCaseProgress($data['uuid'], $data);
    }

    public static function setAbandonCaseData(): array
    {
        $data = [];
        $data['uuid'] = '49895f88-501b-4491-8381-e8aeeaef177d';

        $successMock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], ''),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $failMock = new MockHandler([
            new Response(400, ['X-Foo' => 'Bar'], ''),
        ]);
        $handlerStack = HandlerStack::create($failMock);
        $failClient = new Client(['handler' => $handlerStack]);

        return [
            [
                $data,
                $successClient,
                false,
            ],
            [
                $data,
                $failClient,
                true,
            ],
        ];
    }

    /**
     * @dataProvider serviceAvailabilityData
     */
    public function testServiceAvailability(
        Client $client,
        array $expected,
        bool $exception
    ): void {
        if ($exception) {
            $this->expectException(OpgApiException::class);
        }

        $opgApiService = new OpgApiService($client);

        $this->assertEquals($expected, $opgApiService->getServiceAvailability());
    }

    public static function serviceAvailabilityData(): array
    {
        $successMock = new MockHandler([
            new Response(
                200,
                ['X-Foo' => 'Bar'],
                json_encode([
                    IdMethod::DrivingLicenseNumber->value => true,
                    IdMethod::PassportNumber->value => true,
                    IdMethod::NationalInsuranceNumber->value => true,
                    IdMethod::PostOffice->value => true,
                    'EXPERIAN' => false,
                ], JSON_THROW_ON_ERROR),
            ),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $failMock = new MockHandler([
            new Response(
                200,
                ['X-Foo' => 'Bar'],
                json_encode([], JSON_THROW_ON_ERROR),
            ),
        ]);
        $failHandlerStack = HandlerStack::create($failMock);
        $failClient = new Client(['handler' => $failHandlerStack]);

        return [
            [
                $successClient,
                [
                    "EXPERIAN" => false,
                    "NATIONAL_INSURANCE_NUMBER" => true,
                    "DRIVING_LICENCE" => true,
                    "PASSPORT" => true,
                    "POST_OFFICE" => true,
                ],
                false,
            ],
            [
                $failClient,
                [],
                true,
            ],
        ];
    }

    public function testSendIdentityCheck(): void
    {
        $successMock = new MockHandler([
            function (Request $request) {
                $this->assertEquals('POST', $request->getMethod());
                $this->assertEquals('/cases/case-uuid/send-identity-check', strval($request->getUri()));

                return new Response(200, [], '');
            },
        ]);

        $client = new Client(['handler' => HandlerStack::create($successMock)]);

        $sut = new OpgApiService($client);

        $sut->sendIdentityCheck('case-uuid');
    }

    public function testSendIdentityCheckFailure(): void
    {
        $successMock = new MockHandler([
            function (Request $request) {
                $this->assertEquals('POST', $request->getMethod());
                $this->assertEquals('/cases/case-uuid/send-identity-check', strval($request->getUri()));

                return new Response(404, [], '');
            },
        ]);

        $client = new Client(['handler' => HandlerStack::create($successMock)]);

        $sut = new OpgApiService($client);

        $this->expectException(OpgApiException::class);

        $sut->sendIdentityCheck('case-uuid');
    }
}
