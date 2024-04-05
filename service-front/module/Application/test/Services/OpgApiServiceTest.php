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
     * @dataProvider idOptionsData
     */
    public function testGetIdOptionsData(Client $client, array $responseData, bool $exception): void
    {
        if ($exception) {
            $this->expectException(OpgApiException::class);
        }

        $this->opgApiService = new OpgApiService($client);

        $response = $this->opgApiService->getIdOptionsData();

        $this->assertEquals($responseData, $response);
    }

    public static function idOptionsData(): array
    {
        $successMockResponseData = [
            "Passport",
            "Driving Licence",
            "National Insurance Number"
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
     * @dataProvider detailsData
     */
    public function testGetDetailsData(Client $client, array $responseData, bool $exception): void
    {
        if ($exception) {
            $this->expectException(OpgApiException::class);
        }

        $this->opgApiService = new OpgApiService($client);

        $response = $this->opgApiService->getDetailsData();

        $this->assertEquals($responseData, $response);
    }

    public static function detailsData(): array
    {
        $successMockResponseData = [
            "Name" => "Mary Anne Chapman",
            "DOB" => "01 May 1943",
            "Address" => "Address line 1, line 2, Country, BN1 4OD",
            "Role" => "Donor",
            "LPA" => [
                "PA M-1234-ABCB-XXXX",
                "PW M-1234-ABCD-AAAA"
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
                'lpa_ref' => 'PW M-1234-ABCD-AAAA',
                'donor_name' => 'Mary Anne Chapman'
            ],
            [
                'lpa_ref' => 'PA M-1234-ABCD-XXXX',
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
    public function testValidateNino(string $nino, Client $client, bool $responseData, bool $exception): void
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
        $invalidNino = 'AA112233Q';

        $successMockResponseData = [
            'status' => 'NINO check complete',
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
            new Response(400, ['X-Foo' => 'Bar'], json_encode($failMockResponseData)),
        ]);
        $handlerStack = HandlerStack::create($failMock);
        $failClient = new Client(['handler' => $handlerStack]);

        return [
            [
                $validNino,
                $successClient,
                true,
                false
            ],
            [
                $invalidNino,
                $failClient,
                false,
                false
            ],
        ];
    }


    /**
     * @dataProvider dlnData
     */
    public function testValidateDln(string $dln, Client $client, bool $responseData, bool $exception): void
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
        $invalidDln = 'JONES710238HA3DX';

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
            new Response(400, ['X-Foo' => 'Bar'], json_encode($failMockResponseData)),
        ]);
        $handlerStack = HandlerStack::create($failMock);
        $failClient = new Client(['handler' => $handlerStack]);

        return [
            [
                $validDln,
                $successClient,
                true,
                false
            ],
            [
                $invalidDln,
                $failClient,
                false,
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
}
