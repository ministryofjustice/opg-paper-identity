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
        private string $ssmServiceAvailability,
        private array $config = []
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

    public function healthCheckDependenciesAction(): JsonModel
    {
        $ok = true;
        $dependencies = true;
        $dbConnection = $this->dataQuery->healthCheck();

        $ssmValues = $this->ssmClient->getParameter([
            'Name' => $this->ssmServiceAvailability
        ])->toArray();

        $dependencyStatus = json_decode($ssmValues['Parameter']['Value'], true);

        if (empty($dependencyStatus)) {
            $dependencies = false;
        }

        foreach ($dependencyStatus as $value) {
            if (! $value) {
                $dependencies = false;
            }
        }

        if (! $dbConnection || ! $dependencies) {
            $ok = false;
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_503);
        }

        return new JsonModel([
            'OK' => $ok,
            'dependencies' => [
                'dynamodb' => [
                    'ok' => true
                ],
                'serviceAvailability' => [
                    'ok' => $dependencies,
                    'values' => $dependencyStatus
                ]
            ]
        ]);
    }

    public function serviceAvailabilityAction(): JsonModel
    {
        $status = $this->ssmClient->getParameter([
            'Name' => $this->ssmServiceAvailability
        ])->toArray();

        $services = json_decode($status['Parameter']['Value'], true);

        try {
            $uuid = $this->getRequest()->getQuery('uuid');
            if (! is_null($uuid)) {
                /**
                 * @psalm-suppress PossiblyInvalidCast
                 */
                $case = $this->dataQuery->getCaseByUUID($uuid);

                if (
                    ! is_null($case)
                ) {
                    /**
                     * @psalm-suppress PossiblyNullPropertyFetch
                     */
                    if (
                        $case->fraudScore->decision === 'STOP' ||
                        $case->fraudScore->decision === 'NODECISION' ||
                        $case->kbvResult === 'COMPLETE_FAIL'
                    ) {
                        $services['NATIONAL_INSURANCE_NUMBER'] = false;
                        $services['DRIVING_LICENCE'] = false;
                        $services['PASSPORT'] = false;
                        $services['message'] = "Identity check failure is now restricting ID options.";
                    }
                }
            }
        } catch (\Exception $exception) {
            return new JsonModel($services);
        }
        return new JsonModel($services);
    }
}
