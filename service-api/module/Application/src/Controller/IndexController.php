<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Experian\IIQ\IIQClient;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Psr\Log\LoggerInterface;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 * Needed here due to false positive from Laminas’s uninitialised properties
 */
class IndexController extends AbstractActionController
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly IIQClient $iiqClient,
    ) {
    }

    public function indexAction()
    {
        $request = $this->iiqClient->SAA([
            'sAARequest' => [
                'Applicant' => [
                    'Name' => [
                        'Forename' => 'albert',
                        'Surname' => 'arkil',
                    ],
                ],
            ],
        ]);

        $this->logger->info("found questions", (array)$request->SAAResult->Questions);

        $data = ['Laminas' => 'Paper ID Service API'];

        return new JsonModel($data);
    }
}
