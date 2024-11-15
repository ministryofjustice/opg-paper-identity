<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Aws\SsmHandler;
use Application\Fixtures\DataQueryHandler;
use Application\View\JsonModel;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Psr\Log\LoggerInterface;
use Application\Helpers\ServiceAvailabilityHelper;

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
        private readonly SsmHandler $ssmHandler,
        private string $ssmServiceAvailability,
        private readonly LoggerInterface $logger,
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

    /**
     * @throws \Exception
     */
    public function healthCheckDependenciesAction(): JsonModel
    {
        $ok = true;
        $dependencies = true;
        $dbConnection = $this->dataQuery->healthCheck();

        $dependencyStatus = $this->ssmHandler->getJsonParameter($this->ssmServiceAvailability);

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

    /**
     * @throws \Exception
     */
    public function serviceAvailabilityAction(): JsonModel
    {
        $services = $this->ssmHandler->getJsonParameter($this->ssmServiceAvailability);

        try {
            $uuid = $this->getRequest()->getQuery('uuid');
            if (is_string($uuid)) {
                $case = $this->dataQuery->getCaseByUUID($uuid);

                if (! is_null($case)) {
                    $helper = new ServiceAvailabilityHelper(
                        $services,
                        $case,
                        $this->config
                    );
                    return new JsonModel($helper->processServicesWithCaseData());
                }
            }
        } catch (\Exception $exception) {
            $this->logger->error('Unable to parse Fraudscore data ' . $exception->getMessage());
            return new JsonModel($services);
        }
        return new JsonModel($services);
    }
}
