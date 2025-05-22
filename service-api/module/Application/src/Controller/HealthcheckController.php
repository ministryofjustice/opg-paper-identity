<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Aws\SsmHandler;
use Application\Fixtures\DataQueryHandler;
use Application\View\JsonModel;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Application\Helpers\RouteAvailabilityHelper;
use Application\Model\Entity\Problem;
use Exception;

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
        private string $ssmRouteAvailability,
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

        $dependencyStatus = $this->ssmHandler->getJsonParameter($this->ssmRouteAvailability);

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
                'routeAvailability' => [
                    'ok' => $dependencies,
                    'values' => $dependencyStatus
                ]
            ]
        ]);
    }

    public function routeAvailabilityAction(): JsonModel
    {
        $externalServices = $this->ssmHandler->getJsonParameter($this->ssmRouteAvailability);

        /** @var string $uuid */
        $uuid = $this->getRequest()->getQuery('uuid');
        if (! $uuid) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(new Problem('Missing uuid'));
        }

        $case = $this->dataQuery->getCaseByUUID($uuid);
        if (! $case) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_500);
            return new JsonModel(new Problem("Could not find case {$uuid}"));
        }

        $helper = new RouteAvailabilityHelper(
            $externalServices,
            $this->config
        );
        return new JsonModel($helper->processCase($case));
    }
}
