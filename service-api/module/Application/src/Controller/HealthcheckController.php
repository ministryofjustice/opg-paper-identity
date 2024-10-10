<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Fixtures\DataWriteHandler;
use Application\Fixtures\DataQueryHandler;
use Application\Model\Entity\Problem;
use Application\Yoti\Http\Exception\YotiException;
use Application\Yoti\SessionConfig;
use Application\Yoti\SessionStatusService;
use Application\Yoti\YotiServiceInterface;
use Aws\Ssm\SsmClient;
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
class HealthcheckController extends AbstractActionController
{
    public function __construct(
        private readonly DataQueryHandler $dataQuery,
        private readonly SsmClient $ssmClient,
    ) {
    }

    public function healthCheckAction(): JsonModel
    {
        return new JsonModel([
            'OK' => true
        ]);
    }

    public function healthCheckServiceAction(): JsonModel
    {
        if ($this->dataQuery->healthCheck()) {
            return new JsonModel([
                'OK' => true,
                'dependencies' => [
                    'dynamodb' => [
                        'ok' => true
                    ]
                ]
            ]);
        } else {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_503);
            return new JsonModel([
                'OK' => false,
                'dependencies' => [
                    'dynamodb' => [
                        'ok' => false
                    ]
                ]
            ]);
        }
    }

    public function serviceAvailabilityAction(): JsonModel
    {
        $status = $this->ssmClient->getParameter([
            'Name' => 'service-availability'
        ])->toArray();

        return new JsonModel(json_decode($status['Parameter']['Value'], true));
    }
}
