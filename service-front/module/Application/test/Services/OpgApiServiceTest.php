<?php

declare(strict_types=1);

namespace ApplicationTest\Services;

use Application\Contracts\OpgApiServiceInterface;
use Application\Exceptions\OpgApiException;
use Laminas\Stdlib\ArrayUtils;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use GuzzleHttp\Client;
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

class OpgApiServiceTest extends TestCase
{
    private OpgApiService|MockObject $opgApiService;

    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @dataProvider detailsData
     */
    public function testGetDetailsData(Client $client, array $responseData, bool $exception): void
    {
        if ($exception) {
            $this->expectException(OpgApiException::class);
        }

        $this->opgApiService = new OpgApiService($client);

        $response = $this->opgApiService->getDetailsData('uuid');

        $this->assertEquals($responseData, $response);
    }

    public static function detailsData(): array
    {
        $successMockResponseData = [
            "Name" => "Mary Anne Chapman",
            "DOB" => "01 May 1943",
            "Address" => "Line 1, Line 2, Country, BN1 4OD",
            "Role" => "Donor",
            "LPA" => [
                "PA M-XYXY-YAGA-35G3",
                "PW M-VGAS-OAGA-34G9"
            ]
        ];
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
                $successClient,
                $successMockResponseData,
                false
            ],
            [
                $failClient,
                $failMockResponseData,
                true
            ],
        ];
    }

    /**
     * @dataProvider addressVerificationData
     */
    public function testGetAddressVerificationData(Client $client, array $responseData, bool $exception): void
    {
        if ($exception) {
            $this->expectException(OpgApiException::class);
        }

        $this->opgApiService = new OpgApiService($client);

        $response = $this->opgApiService->getAddressVerificationData();

        $this->assertEquals($responseData, $response);
    }

    public static function addressVerificationData(): array
    {
        $successMockResponseData = [
            'Passport',
            'Driving Licence',
            'National Insurance Number',
            'Voucher',
            'Post Office',
        ];
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
                $successClient,
                $successMockResponseData,
                false
            ],
            [
                $failClient,
                $failMockResponseData,
                true
            ],
        ];
    }

    /**
     * @dataProvider lpasByDonorData
     */
    public function testGetLpasByDonorData(Client $client, array $responseData, bool $exception): void
    {
        if ($exception) {
            $this->expectException(OpgApiException::class);
        }

        $this->opgApiService = new OpgApiService($client);

        $response = $this->opgApiService->getLpasByDonorData();

        $this->assertEquals($responseData, $response);
    }

    public static function lpasByDonorData(): array
    {
        $successMockResponseData = [
            [
                'lpa_ref' => "PA M-XYXY-YAGA-35G3",
                'donor_name' => 'Mary Anne Chapman'
            ],
            [
                'lpa_ref' => "PW M-VGAS-OAGA-34G9",
                'donor_name' => 'Mary Anne Chapman'
            ]
        ];
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
                $successClient,
                $successMockResponseData,
                false
            ],
            [
                $failClient,
                $failMockResponseData,
                true
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
            'nino' => $validNino
        ];

        $successMock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], json_encode($successMockResponseData)),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $failMockResponseData = [
            'status' => 'NO_MATCH',
            'nino' => $invalidNino
        ];
        $failMock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], json_encode($failMockResponseData)),
        ]);
        $handlerStack = HandlerStack::create($failMock);
        $failClient = new Client(['handler' => $handlerStack]);

        $insufficientMockResponseData = [
            'status' => 'NOT_ENOUGH_DETAILS',
            'nino' => $insufficientNino
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
                false
            ],
            [
                $invalidNino,
                $failClient,
                'NO_MATCH',
                false
            ],
            [
                $insufficientNino,
                $insufficientClient,
                'NOT_ENOUGH_DETAILS',
                false
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
            'dln' => $validDln
        ];

        $successMock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], json_encode($successMockResponseData)),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $failMockResponseData = [
            'status' => 'NO_MATCH',
            'dln' => $invalidDln
        ];
        $failMock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], json_encode($failMockResponseData)),
        ]);
        $handlerStack = HandlerStack::create($failMock);
        $failClient = new Client(['handler' => $handlerStack]);

        $insufficientMockResponseData = [
            'status' => 'NOT_ENOUGH_DETAILS',
            'dln' => $insufficientDln
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
                false
            ],
            [
                $invalidDln,
                $failClient,
                'NO_MATCH',
                false
            ],
            [
                $insufficientDln,
                $insufficientClient,
                'NOT_ENOUGH_DETAILS',
                false
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
            'passport' => $validPassport
        ];

        $successMock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], json_encode($successMockResponseData)),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $failMockResponseData = [
            'status' => 'NO_MATCH',
            'passport' => $invalidPassport
        ];
        $failMock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], json_encode($failMockResponseData)),
        ]);
        $handlerStack = HandlerStack::create($failMock);
        $failClient = new Client(['handler' => $handlerStack]);

        $insufficientMockResponseData = [
            'status' => 'NOT_ENOUGH_DETAILS',
            'passport' => $insufficientPassport
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
                false
            ],
            [
                $invalidPassport,
                $failClient,
                'NO_MATCH',
                false
            ],
            [
                $insufficientPassport,
                $insufficientClient,
                'NOT_ENOUGH_DETAILS',
                false
            ],
        ];
    }

    public function testGetIdCheckQuestions(): void
    {
        $uuid = '49895f88-501b-4491-8381-e8aeeaef177d';

        $mockResponseData = [
            "one" => [
                "id" => 1,
                "question" => "Who provides your mortgage?",
                "number" => "one",
                "prompts" => [
                    0 => "Nationwide",
                    1 => "Halifax",
                    2 => "Lloyds",
                    3 => "HSBC",
                ]
            ],
            "two" => [
                "id" => 2,
                "question" => "Who provides your personal mobile contract?",
                "number" => "two",
                "prompts" => [
                    0 => "EE",
                    1 => "Vodafone",
                    2 => "BT",
                    3 => "iMobile",
                ]
            ],
            "three" => [
                "id" => 3,
                "question" => "What are the first two letters of the last name of another person 
                on the electroal register at your address?",
                "number" => "three",
                "prompts" => [
                    0 => "Ka",
                    1 => "Ch",
                    2 => "Jo",
                    3 => "None of the above",
                ]
            ],
            "four" => [
                "id" => 4,
                "question" => "Who provides your current account?",
                "number" => "four",
                "prompts" => [
                    0 => "Santander",
                    1 => "HSBC",
                    2 => "Halifax",
                    3 => "Nationwide",
                ]
            ]
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
    public function testCheckIdCheckAnswers(array $answers, Client $client, bool $responseData, bool $exception): void
    {
        if ($exception) {
            $this->expectException(OpgApiException::class);
        }
        $uuid = '49895f88-501b-4491-8381-e8aeeaef177d';

        $this->opgApiService = new OpgApiService($client);

        $response = $this->opgApiService->checkIdCheckAnswers($uuid, $answers);

        $this->assertEquals($responseData, $response);
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
            "result" => "pass"
        ];

        $failResponse = [
            "result" => "fail"
        ];

        $successMock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], json_encode($correctResponse)),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $failMock = new MockHandler([
            new Response(400, ['X-Foo' => 'Bar'], json_encode($failResponse)),
        ]);
        $handlerStack = HandlerStack::create($failMock);
        $failClient = new Client(['handler' => $handlerStack]);

        return [
            [
                $correctAnswers,
                $successClient,
                true,
                false
            ],
            [
                $wrongAnswers,
                $failClient,
                false,
                false
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
            "PW M-VGAS-OAGA-34G9"
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
                "PostOfficePostcode"
            ]
        ];

        $successMockResponseData = [
            "case_uuid" => $uuid,
            "name" => $postData['FirstName'] . " " . $postData['LastName'],
            "lpas" => $lpas
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
                false
            ],
            [
                $postData,
                $failClient,
                $failMockResponseData,
                true
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
            "LPA_Number" => "M-0000-0000-0000",
            "Type_Of_LPA" => "Personal welfare",
            "Donor" => "Mary Ann Chapman",
            "Status" => "Processing",
            "CP_Name" => "David Smith",
            "CP_Address" => [
                "Line_1" => "82 Penny Street",
                "Line_2" => "Lancaster",
                "Town" => "Lancashire",
                "PostOfficePostcode" => "LA1 1XN",
                "Country" => "United Kingdom"
            ],
            "message" => "Success",
            "status" => 200
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
                false
            ],
            [
                $data,
                $failClient,
                $failMockResponseData,
                true
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
                false
            ],
            [
                $data,
                $failClient,
                $failMockResponseData,
                true
            ],
        ];
    }

    /**
     * @dataProvider addPostcodeSearchData
     * @return void
     */
    public function testAddPostcodeSearchMethod(array $data, Client $client, array $responseData, bool $exception): void
    {
        if ($exception) {
            $this->expectException(OpgApiException::class);
        }
        $this->opgApiService = new OpgApiService($client);

        $response = $this->opgApiService->addSearchPostcode($data['uuid'], $data['selected_postcode']);

        $this->assertEquals($responseData, $response);
    }

    public static function addPostcodeSearchData(): array
    {
        $data = [];
        $data['uuid'] = '49895f88-501b-4491-8381-e8aeeaef177d';
        $data['selected_postcode'] = "SW1A 1AA";

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
                false
            ],
            [
                $data,
                $failClient,
                $failMockResponseData,
                true
            ],
        ];
    }
}
