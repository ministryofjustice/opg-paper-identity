<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Psr\Log\LoggerInterface;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 * @psalm-suppress InvalidArgument
 * @psalm-suppress UnusedProperty
 * Needed here due to false positive from Laminas’s uninitialised properties
 */
class IndexController extends AbstractActionController
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function indexAction()
    {
        $this->logger->info('>>cert file size:' . filesize('/opg-private/experian-iiq-cert.pem'));

        $data = ['Laminas' => 'Paper ID Service API'];
        return new JsonModel($data);
    }
}
