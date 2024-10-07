<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Fixtures\DataWriteHandler;
use Application\Fixtures\DataQueryHandler;
use Application\Model\Entity\Problem;
use Application\Services\SiriusApiService;
use Application\Yoti\Http\Exception\YotiException;
use Application\Yoti\SessionConfig;
use Application\Yoti\SessionStatusService;
use Application\Yoti\YotiServiceInterface;
use DateTime;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Http\Response;
use Application\View\JsonModel;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 * @psalm-suppress InvalidArgument
 * @psalm-suppress UnusedProperty
 * Needed here due to false positive from Laminas’s uninitialised properties
 */
class HealthCheckController extends AbstractActionController
{
    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly SiriusApiService $siriusApiService,
    )
    {
    }

    public function healthCheckAction(): JsonModel
    {
        return new JsonModel([
            'OK' => true
        ]);
    }

    public function healthCheckServiceAction(): JsonModel
    {
        $ok = true;

        $siriusResponse = $this->siriusApiService->checkAuth($this->getRequest());

        if ($siriusResponse !== true) {
            $ok = false;
        }

        $apiResponse = $this->opgApiService->healthCheck();

        if ($apiResponse !== true) {
            $ok = false;
        }

        return new JsonModel([
            'OK' => $ok,
            'dependencies' => [
                'sirius' => [
                    'ok' => $siriusResponse
                ],
                'api' => [
                    'ok' => $apiResponse
                ]
            ]
        ]);
    }
}
