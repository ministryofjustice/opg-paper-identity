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
            'status' => 'valid',
            'nino' => $validNino
        ];

        $successMock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], json_encode($successMockResponseData)),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $failMockResponseData = [
            'status' => 'not valid',
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
}
