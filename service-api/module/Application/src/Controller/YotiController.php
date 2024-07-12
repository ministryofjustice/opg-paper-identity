<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Exceptions\YotiException;
use Application\Fixtures\DataImportHandler;
use Application\Model\Entity\Problem;
use Application\Yoti\YotiServiceInterface;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Http\Response;
use Application\View\JsonModel;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 * @psalm-suppress InvalidArgument
 * Needed here due to false positive from Laminas’s uninitialised properties
 */
class YotiController extends AbstractActionController
{
    public function __construct(
        private readonly YotiServiceInterface $yotiService,
        private readonly DataImportHandler $dataImportHandler,
    ) {
    }

    /**
     * @return JsonModel
     * @psalm-suppress PossiblyInvalidArgument
     */
    public function findPostOfficeAction(): JsonModel
    {
        $branches = [];
        $data = json_decode($this->getRequest()->getContent(), true);

        if (! isset($data["search_string"])) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(new Problem('Missing search parameter'));
        }
        try {
            $branchList = $this->yotiService->postOfficeBranch($data["search_string"]);
            foreach ($branchList['branches'] as $branch) {
                $branches[$branch["fad_code"]] = [
                    "name" => $branch["name"],
                    "address" => $branch["address"],
                    "postcode" => $branch["postcode"]
                ];
            }
        } catch (YotiException $e) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(new Problem(
                'Service issue',
                extra: ['errors' => $e->getMessage()],
            ));
        }
        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);
        return new JsonModel($branches);
    }
    public function createSessionAction(array $sessionData): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        //@TODO authenticate if not using mock?
        $result = $this->yotiService->createSession($sessionData);
        //save sessionId back to caseData
        if ($result["status"] < 400) {
            $this->dataImportHandler->updateCaseData(
                $uuid,
                'sessionId',
                'S',
                $result["data"]["session_id"]
            );
        }
        $this->getResponse()->setStatusCode(Response::STATUS_CODE_204);
        return new JsonModel(null);
    }

    public function getSessionStatusAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');

        if (! $uuid) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(['error' => 'Missing uuid']);
        }
        //@TODO look up actual sessionId from case and case where this is not created
        $sessionId = 'AJDAHDFSH';
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
