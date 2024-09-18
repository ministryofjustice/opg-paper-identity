<?php

declare(strict_types=1);

namespace ApplicationTest\Services;

use Application\Exceptions\HttpException;
use Application\Exceptions\OpgApiException;
use Application\Services\OpgApiService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Throwable;

class OpgApiServiceTest extends TestCase
{
    private OpgApiService|MockObject $opgApiService;

    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @dataProvider detailsData
     * @param class-string<Throwable>|null $expectedException
     */
    public function testGetDetailsData(Client $client, ?array $responseData, ?string $expectedException): void
    {
        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        $this->opgApiService = new OpgApiService($client);

        $response = $this->opgApiService->getDetailsData('uuid');

        $this->assertEquals($responseData, $response);
    }

    public static function detailsData(): array
    {
        $successMockResponseData = [
            "firstName" => "Mary Ann",
            "lastName" => "Chapman",
            "dob" => "01 May 1943",
            "address" => [
                'line1' => '1 Street',
                'line2' => '',
                'line3' => '',
                'town' => 'Middleton',
                'postcode' => 'LA1 2XN',
                'country' => 'DD',
            ],
            "personType" => "donor",
            "lpas" => [
                "PA M-XYXY-YAGA-35G3",
                "PW M-VGAS-OAGA-34G9",
            ],
        ];
        $successMock = new MockHandler([
            new Response(200, [], json_encode($successMockResponseData)),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $failMock = new MockHandler([
            new Response(400, [], json_encode(['Bad Request'])),
        ]);
        $handlerStack = HandlerStack::create($failMock);
        $failClient = new Client(['handler' => $handlerStack]);

        $notFoundMock = new MockHandler([
            new Response(404, [], json_encode(['error' => 'Case not found'])),
        ]);
        $handlerStack = HandlerStack::create($notFoundMock);
        $notFoundClient = new Client(['handler' => $handlerStack]);

        return [
            [
                $successClient,
                $successMockResponseData,
                null,
            ],
            [
                $failClient,
                null,
                OpgApiException::class,
            ],
            [
                $notFoundClient,
                null,
                HttpException::class,
            ],
        ];
    }

    /**
     * @dataProvider ninoData
     */
    public function testValidateNino(string $nino, Client $client, string $responseData, bool $exception): void
    {
        if ($exception) {
            $this->expectException(OpgApiException::class);
        }

        $this->opgApiService = new OpgApiService($client);

        $response = $this->opgApiService->checkNinoValidity($nino);

        $this->assertEquals($responseData, $response);
    }

    public static function ninoData(): array
    {
        $validNino = 'AA112233A';
        $invalidNino = 'AA112233C';
        $insufficientNino = 'AA112233D';

        $successMockResponseData = [
            'status' => 'PASS',
            'nino' => $validNino,
        ];

        $successMock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], json_encode($successMockResponseData)),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $failMockResponseData = [
            'status' => 'NO_MATCH',
            'nino' => $invalidNino,
        ];
        $failMock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], json_encode($failMockResponseData)),
        ]);
        $handlerStack = HandlerStack::create($failMock);
        $failClient = new Client(['handler' => $handlerStack]);

        $insufficientMockResponseData = [
            'status' => 'NOT_ENOUGH_DETAILS',
            'nino' => $insufficientNino,
        ];
        $insufficientMock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], json_encode($insufficientMockResponseData)),
        ]);
        $handlerStack = HandlerStack::create($insufficientMock);
        $insufficientClient = new Client(['handler' => $handlerStack]);

        return [
            [
                $validNino,
                $successClient,
                'PASS',
                false,
            ],
            [
                $invalidNino,
                $failClient,
                'NO_MATCH',
                false,
            ],
            [
                $insufficientNino,
                $insufficientClient,
                'NOT_ENOUGH_DETAILS',
                false,
            ],
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

        $this->opgApiService = new OpgApiService($client);

        $response = $this->opgApiService->checkDlnValidity($dln);

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
            new Response(200, ['X-Foo' => 'Bar'], json_encode($successMockResponseData)),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $failMockResponseData = [
            'status' => 'NO_MATCH',
            'dln' => $invalidDln,
        ];
        $failMock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], json_encode($failMockResponseData)),
        ]);
        $handlerStack = HandlerStack::create($failMock);
        $failClient = new Client(['handler' => $handlerStack]);

        $insufficientMockResponseData = [
            'status' => 'NOT_ENOUGH_DETAILS',
            'dln' => $insufficientDln,
        ];
        $insufficientMock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], json_encode($insufficientMockResponseData)),
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

        $this->opgApiService = new OpgApiService($client);

        $response = $this->opgApiService->checkPassportValidity($passport);

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
            new Response(200, ['X-Foo' => 'Bar'], json_encode($successMockResponseData)),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $failMockResponseData = [
            'status' => 'NO_MATCH',
            'passport' => $invalidPassport,
        ];
        $failMock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], json_encode($failMockResponseData)),
        ]);
        $handlerStack = HandlerStack::create($failMock);
        $failClient = new Client(['handler' => $handlerStack]);

        $insufficientMockResponseData = [
            'status' => 'NOT_ENOUGH_DETAILS',
            'passport' => $insufficientPassport,
        ];
        $insufficientMock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], json_encode($insufficientMockResponseData)),
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
            new Response(200, ['X-Foo' => 'Bar'], json_encode($mockResponseData)),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $this->opgApiService = new OpgApiService($client);

        $response = $this->opgApiService->getIdCheckQuestions($uuid);

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

        $this->opgApiService = new OpgApiService($client);

        $response = $this->opgApiService->checkIdCheckAnswers($uuid, $answers);

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
            new Response(200, ['X-Foo' => 'Bar'], json_encode($correctResponse)),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $failMock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], json_encode($failResponse)),
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
        $this->opgApiService = new OpgApiService($client);

        $response = $this->opgApiService->createCase(
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
            new Response(200, ['X-Foo' => 'Bar'], json_encode($successMockResponseData)),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $failMockResponseData = ['error' => 'POST /cases/create resulted in a `400 Bad Request`'];
        $failMock = new MockHandler([
            new Response(400, ['X-Foo' => 'Bar'], json_encode($failMockResponseData)),
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
     * @dataProvider addLpaData
     * @return void
     */
    public function testFindLpa(array $data, Client $client, array $responseData, bool $exception): void
    {
        $this->opgApiService = new OpgApiService($client);

        $response = $this->opgApiService->findLpa(
            $data['uuid'],
            $data['lpa'],
        );

        if ($exception) {
            $this->assertStringContainsString($responseData[0], json_encode($response));
        } else {
            $this->assertEquals($responseData, $response);
        }
    }

    public static function addLpaData(): array
    {
        $data = [];
        $data['uuid'] = '49895f88-501b-4491-8381-e8aeeaef177d';
        $data['lpa'] = "PA M-XYXY-YAGA-35G3";

        $successMockResponseData = [
            "case_uuid" => "9130a21e-6e5e-4a30-8b27-76d21b747e60",
            "lpa_number" => "M-0000-0000-0000",
            "type_of_lpa" => "Personal welfare",
            "donor" => "Mary Ann Chapman",
            "lpa_status" => "Processing",
            "cp_name" => "David Smith",
            "cp_address" => [
                "Line_1" => "82 Penny Street",
                "Line_2" => "Lancaster",
                "Town" => "Lancashire",
                "Postcode" => "LA1 1XN",
                "Country" => "United Kingdom",
            ],
            "message" => "Success",
            "status" => 200,
        ];
        $successMock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], json_encode($successMockResponseData)),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $failMockResponseData = ['Client error'];
        $failMock = new MockHandler([
            new Response(400, ['X-Foo' => 'Bar'], json_encode($failMockResponseData)),
        ]);
        $handlerStack = HandlerStack::create($failMock);
        $failClient = new Client(['handler' => $handlerStack]);

        return [
            [
                $data,
                $successClient,
                $successMockResponseData,
                false,
            ],
            [
                $data,
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
    public function testUpdateIdMethod(array $data, Client $client, array $responseData, bool $exception): void
    {
        if ($exception) {
            $this->expectException(OpgApiException::class);
        }
        $this->opgApiService = new OpgApiService($client);

        $response = $this->opgApiService->updateIdMethod($data['uuid'], $data['method']);

        $this->assertEquals($responseData, $response);
    }

    public static function updateIdMethodData(): array
    {
        $data = [];
        $data['uuid'] = '49895f88-501b-4491-8381-e8aeeaef177d';
        $data['method'] = "nin";

        $successMockResponseData = ["result" => "Updated"];
        $successMock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], json_encode($successMockResponseData)),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $failMockResponseData = ['Bad Request'];
        $failMock = new MockHandler([
            new Response(400, ['X-Foo' => 'Bar'], json_encode($failMockResponseData)),
        ]);
        $handlerStack = HandlerStack::create($failMock);
        $failClient = new Client(['handler' => $handlerStack]);

        return [
            [
                $data,
                $successClient,
                $successMockResponseData,
                false,
            ],
            [
                $data,
                $failClient,
                $failMockResponseData,
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
        array $responseData,
        bool $exception
    ): void {
        if ($exception) {
            $this->expectException(OpgApiException::class);
        }
        $this->opgApiService = new OpgApiService($client);

        $response = $this->opgApiService->updateCaseSetDocumentComplete($data['uuid']);

        if ($exception) {
            $this->assertStringContainsString('Client error:', json_encode($response));
        } else {
            $this->assertEquals($responseData, $response);
        }
    }

    public static function setDocumentCompleteData(): array
    {
        $data = [];
        $data['uuid'] = '49895f88-501b-4491-8381-e8aeeaef177d';

        $successMockResponseData = ["result" => "Updated"];
        $successMock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], json_encode($successMockResponseData)),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $failMockResponseData = [
            'Client error: `',
        ];
        $failMock = new MockHandler([
            new Response(400, ['X-Foo' => 'Bar'], json_encode($failMockResponseData)),
        ]);
        $handlerStack = HandlerStack::create($failMock);
        $failClient = new Client(['handler' => $handlerStack]);

        return [
            [
                $data,
                $successClient,
                $successMockResponseData,
                false,
            ],
            [
                $data,
                $failClient,
                $failMockResponseData,
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
        array $responseData,
        bool $exception
    ): void {
        if ($exception) {
            $this->expectException(OpgApiException::class);
        }
        $this->opgApiService = new OpgApiService($client);

        $response = $this->opgApiService->updateCaseSetDob($data['uuid'], $data['dob']);

        if ($exception) {
            $this->assertStringContainsString('Client error:', json_encode($response));
        } else {
            $this->assertEquals($responseData, $response);
        }
    }

    public static function setDobData(): array
    {
        $data = [];
        $data['uuid'] = '49895f88-501b-4491-8381-e8aeeaef177d';
        $data['dob'] = '1980-01-01';

        $successMockResponseData = ["result" => "Updated"];
        $successMock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], json_encode($successMockResponseData)),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $failMockResponseData = [
            'Client error: `',
        ];
        $failMock = new MockHandler([
            new Response(400, ['X-Foo' => 'Bar'], json_encode($failMockResponseData)),
        ]);
        $handlerStack = HandlerStack::create($failMock);
        $failClient = new Client(['handler' => $handlerStack]);

        return [
            [
                $data,
                $successClient,
                $successMockResponseData,
                false,
            ],
            [
                $data,
                $failClient,
                $failMockResponseData,
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
        array $responseData,
        bool $exception
    ): void {
        if ($exception) {
            $this->expectException(OpgApiException::class);
        }
        $this->opgApiService = new OpgApiService($client);

        $response = $this->opgApiService->updateCaseProgress($data['uuid'], $data);

        if ($exception) {
            $this->assertStringContainsString('Client error:', json_encode($response));
        } else {
            $this->assertEquals($responseData, $response);
        }
    }

    public static function setAbandonCaseData(): array
    {
        $data = [];
        $data['uuid'] = '49895f88-501b-4491-8381-e8aeeaef177d';
        $data['dob'] = '1980-01-01';

        $successMockResponseData = ["result" => "Updated"];
        $successMock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], json_encode($successMockResponseData)),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $failMockResponseData = [
            'Client error: `',
        ];
        $failMock = new MockHandler([
            new Response(400, ['X-Foo' => 'Bar'], json_encode($failMockResponseData)),
        ]);
        $handlerStack = HandlerStack::create($failMock);
        $failClient = new Client(['handler' => $handlerStack]);

        return [
            [
                $data,
                $successClient,
                $successMockResponseData,
                false,
            ],
            [
                $data,
                $failClient,
                $failMockResponseData,
                true,
            ],
        ];
    }
}
