<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Yoti\YotiServiceInterface;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Http\Response;
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

    public function getSessionStatusAction(): JsonModel
    {
        $sessionId = $this->params()->fromRoute('sessionId');

        if (! $sessionId) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(['error' => 'Missing sessionId']);
        }

        $session = $this->yotiService->retrieveResults($sessionId);

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);
        $data = ['status' => $session['state']];

        return new JsonModel($data);
    }

    public function getPDFLetterAction(string $session): JsonModel
    {
        $data = [];
        $data['response'] = $this->yotiService->retrieveLetterPDF($session);
        return new JsonModel($data);
    }
}
