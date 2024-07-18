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
use DateTime;
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
    public function initiateCounterServiceAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        if (! $uuid) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(['error' => 'Missing uuid']);
        }
        $notificationsAuthToken = strval(Uuid::uuid4());
        $caseData = $this->dataQuery->getCaseByUUID($uuid);

        if (! $caseData) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(['error' => 'Case data not found']);
        }
        $sessionData = $this->sessionConfig->build($caseData, $notificationsAuthToken);
        $nonce = strval(Uuid::uuid4());
        $dateTime = new DateTime();
        $timestamp = $dateTime->getTimestamp();

        try {
            $result = $this->yotiService->createSession($sessionData, $nonce, $timestamp);
            $yotiSessionId = $result["data"]["session_id"];
            $counterServiceMap = [];
            //need to add back existing values so it doesn't delete them
            if ($caseData->counterService !== null) {
                $counterServiceMap["selectedPostOffice"] = $caseData->counterService->selectedPostOffice;
                $counterServiceMap["selectedPostOfficeDeadline"] = $caseData->counterService->selectedPostOfficeDeadline;
            }
            $counterServiceMap["sessionId"] = $yotiSessionId;
            $counterServiceMap["notificationsAuthToken"] = $notificationsAuthToken;

            if ($result["status"] < 400) {
                $this->dataImportHandler->updateCaseData(
                    $uuid,
                    'counterService',
                    'M',
                    array_map(fn (mixed $v) => [
                        'S' => $v
                    ], $counterServiceMap),
                );
            }
            //Prepare and generate PDF
            $this->yotiService->preparePDFLetter($caseData, $nonce, $timestamp, $yotiSessionId);
            $this->yotiService->retrieveLetterPDF($yotiSessionId, $nonce, $timestamp);
            //@TODO send pdf from above to sirius when ready
        } catch (YotiException $e) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_500);
            return new JsonModel(new Problem(
                'Problem requesting Yoti API',
                extra: ['errors' => $e->getMessage()],
            ));
        }
        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);
        return new JsonModel(['counter-service-status' => 'started']);
    }
}
