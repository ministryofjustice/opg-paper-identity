<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Fixtures\DataImportHandler;
use Application\Fixtures\DataQueryHandler;
use Application\Model\Entity\CaseData;
use Application\Model\Entity\Problem;
use Application\Yoti\Http\Exception\YotiException;
use Application\Yoti\SessionConfig;
use Application\Yoti\YotiServiceInterface;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Http\Response;
use Application\View\JsonModel;
use Ramsey\Uuid\Uuid;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 * @psalm-suppress InvalidArgument
 * @psalm-suppress UnusedProperty
 * Needed here due to false positive from Laminas’s uninitialised properties
 */
class YotiController extends AbstractActionController
{
    public function __construct(
        private readonly YotiServiceInterface $yotiService,
        private readonly DataImportHandler $dataImportHandler,
        private readonly DataQueryHandler $dataQuery,
        private readonly SessionConfig $sessionConfig,
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
            return new JsonModel(new Problem('Missing postCode'));
        }
        try {
            $branchList = $this->yotiService->postOfficeBranch($data["search_string"]);
            foreach ($branchList['branches'] as $branch) {
                $branches[$branch["fad_code"]] = [
                    "name" => $branch["name"],
                    "address" => $branch["address"] . ", " . $branch["postcode"]
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
    public function createSessionAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        $override = $this->params()->fromQuery('overrideToken');

        if (! $uuid) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(['error' => 'Missing uuid']);
        }

        $notifyAuthToken = $override ?? strval(Uuid::uuid4());
        $caseData = $this->dataQuery->getCaseByUUID($uuid);

        if (! $caseData) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(['error' => 'Case data not found']);
        }
        $sessionData = $this->sessionConfig->build($caseData, $notifyAuthToken);

        try {
            $result = $this->yotiService->createSession($sessionData);

            if ($result["status"] < 400) {
                $this->dataImportHandler->updateCaseData(
                    $uuid,
                    'sessionId',
                    'S',
                    $result["data"]["session_id"]
                );
                $this->dataImportHandler->updateCaseData(
                    $uuid,
                    'notifyAuthToken',
                    'S',
                    $notifyAuthToken
                );
            }
        } catch (YotiException $e) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_500);
            return new JsonModel(new Problem(
                'Problem requesting Yoti API',
                extra: ['errors' => $e->getMessage()],
            ));
        }
        $this->getResponse()->setStatusCode(Response::STATUS_CODE_201);
        return new JsonModel($result);
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

    /**
     * @throws YotiException
     */
    public function preparePDFLetterAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        if (! $uuid) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(['error' => 'Missing uuid']);
        }

        $caseData = $this->dataQuery->getCaseByUUID($uuid);

        if (! $caseData || $caseData->sessionId === null) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(new Problem('SessionId does not exist to prepare PDF'));
        }

        $data = [];
        try {
            $data['response'] = $this->yotiService->preparePDFLetter($caseData);
        } catch (YotiException $e) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_500);
            return new JsonModel(new Problem(
                'Problem preparing PDF Yoti API',
                extra: ['errors' => $e->getMessage()],
            ));
        }

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);
        return new JsonModel($data);
    }

    public function retrievePDFAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        $caseData = $this->dataQuery->getCaseByUUID($uuid);
        try {
            $data = $this->yotiService->retrieveLetterPDF($caseData);
        } catch (YotiException $e) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_500);
            return new JsonModel(new Problem(
                'Problem retrieving PDF Yoti API',
                extra: ['errors' => $e->getMessage()],
            ));
        }
        // Write pdf to file
        $fileName = 'instructions-' . $uuid . '.pdf';
        $content = file_put_contents($fileName, $data['pdfData']);

        $this->getResponse()->setContent($content);
        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);
        return new JsonModel(["Status" => "PDF Created", "pdfData" => $data['pdfData']]);
    }
}
