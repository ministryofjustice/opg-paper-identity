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

class OpgApiServiceTest extends AbstractHttpControllerTestCase
{
    private OpgApiService|MockObject $opgApiService;

    /**
     * @var string[]
     */
    private array $config;

    public function setUp(): void
    {
        // The module configuration should still be applicable for tests.
        // You can override configuration here with test case specific values,
        // such as sample view templates, path stacks, module_listener_options,
        // etc.
        $configOverrides = [];

        $this->setApplicationConfig(ArrayUtils::merge(
            include __DIR__ . '/../../../../config/application.config.php',
            $configOverrides
        ));

        $this->config = ['base-url' => 'testing'];

        parent::setUp();
    }

    /**
     * @dataProvider idOptionsData
     */
    public function testGetIdOptionsData(Client $client, array|string $responseData, bool $exception): void
    {
        $mockResponseData = [
            "Passport",
            "Driving Licence",
            "National Insurance Number"
        ];

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], json_encode($mockResponseData)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $this->opgApiService = new OpgApiService($client, $this->config);
        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(OpgApiServiceInterface::class, $this->opgApiService);

        $response = $this->opgApiService->getIdOptionsData();

        $this->assertEquals($mockResponseData, $response);
    }

    static public function idOptionsData()
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

        $failMockResponseData = 'Bad Request';
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
    public function testGetDetailsData(Client $client, array|string $responseData, bool $exception): void
    {
        if($exception) {
            $this->expectException(OpgApiException::class);
        }

        $this->opgApiService = new OpgApiService($client, $this->config);
        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(OpgApiServiceInterface::class, $this->opgApiService);

        $response = $this->opgApiService->getDetailsData();

        $this->assertEquals($responseData, $response);
    }

    static public function detailsData()
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

        $failMockResponseData = 'Bad Request';
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
}
