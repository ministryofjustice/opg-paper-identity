<?php

declare(strict_types=1);

namespace ApplicationTest\Services\DWP\DwpApi;

use Application\Cache\ApcHelper;
use Application\DWP\AuthApi\AuthApiService;
use Application\DWP\AuthApi\DTO\RequestDTO;
use Application\DWP\DwpApi\DwpApiService;
use Application\DWP\DwpApi\DTO\CitizenRequestDTO;
use Application\DWP\DwpApi\DTO\DetailsRequestDTO;
use GuzzleHttp\Client;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Throwable;

class DwpApiServiceTest extends TestCase
{
    private Client $client;
    private Client $clientCitizen;

    private Client $clientMatch;

    private ApcHelper $apcHelper;

    private RequestDTO $dwpAuthRequestDto;

    private CitizenRequestDTO $citizenRequestDTO;

    private AuthApiService $dwpAuthApiService;

    private DwpApiService $dwpApiService;

    private LoggerInterface&MockObject $logger;

    public function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->clientCitizen = $this->createMock(Client::class);
        $this->clientMatch = $this->createMock(Client::class);
        $this->client = $this->createMock(Client::class);
        $this->apcHelper = $this->createMock(ApcHelper::class);
        $this->dwpAuthRequestDto = new RequestDTO(
            'username',
            'password',
            'bundle',
            'privateKey',
        );

        $this->dwpAuthApiService = new AuthApiService(
            $this->client,
            $this->apcHelper,
            $this->logger,
            $this->dwpAuthRequestDto
        );

        $this->dwpApiService = new DwpApiService(
            $this->clientCitizen,
            $this->clientMatch,
            $this->dwpAuthApiService,
            $this->logger,
            []
        );
    }

    /**
     * @dataProvider ninoData
     */
    public function testNinoFragment(string $nino, string $fragment): void
    {
        $this->assertEquals($fragment, $this->dwpApiService->makeNinoFragment($nino));
    }

    public static function ninoData(): array
    {
        return [
            [
                "AA 12 23 34 C",
                "334C"
            ],
            [
                "AA122334C",
                "334C"
            ],
            [
                " AA 12 23 34 C ",
                "334C"
            ],
            [
                " AA122334C ",
                "334C"
            ],
        ];
    }
}
