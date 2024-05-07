<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Yoti\YotiServiceInterface;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;

class YotiController extends AbstractActionController
{
    public function __construct(
        private readonly YotiServiceInterface $yotiService
    ) {
    }

    public function findPostOffice(string $postCode)
    {
        $branches = [];
        return new JsonModel($branches);
    }
    public function createSessionAction(array $sessionData)
    {
        $data = [];
        return new JsonModel($data);
    }

    public function getSessionAction(string $sessionId)
    {
        $data = [];
        return new JsonModel($data);
    }

    public function getPDFLetter(string $session)
    {
        $data = [];
        return new JsonModel($data);
    }
}
