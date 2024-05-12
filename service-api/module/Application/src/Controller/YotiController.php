<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Yoti\YotiServiceInterface;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 * Needed here due to false positive from Laminas’s uninitialised properties
 */
class YotiController extends AbstractActionController
{
    public function __construct(
        private readonly YotiServiceInterface $yotiService
    ) {
    }

    public function findPostOfficeAction(string $postCode): JsonModel
    {
        $branches = [];
        $branches["locations"] = $this->yotiService->postOfficeBranch($postCode);
        return new JsonModel($branches);
    }
    public function createSessionAction(array $sessionData): JsonModel
    {
        $data = [];
        $data['response'] = $this->yotiService->createSession($sessionData);
        return new JsonModel($data);
    }

    public function getSessionAction(): JsonModel
    {
        $sessionId = $this->params()->fromRoute('sessionId');
        $data = [];
        $data['response'] = $this->yotiService->retrieveResults($sessionId);
        return new JsonModel($data);
    }

    public function getPDFLetterAction(string $session): JsonModel
    {
        $data = [];
        $data['response'] = $this->yotiService->retrieveLetterPDF($session);
        return new JsonModel($data);
    }
}
