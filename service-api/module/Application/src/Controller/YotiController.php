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

    public function findPostOffice(string $postCode): JsonModel
    {
        $branches = [];
        return new JsonModel($branches);
    }
    public function createSessionAction(array $sessionData): JsonModel
    {
        $data = [];
        return new JsonModel($data);
    }

    public function getSessionAction(string $sessionId): JsonModel
    {
        $data = [];
        return new JsonModel($data);
    }

    public function getPDFLetter(string $session): JsonModel
    {
        $data = [];
        return new JsonModel($data);
    }
}
