<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Fixtures\DataImportHandler;
use Application\Fixtures\DataQueryHandler;
use Application\Model\Entity\Problem;
use Application\Yoti\Http\Exception\YotiException;
use Application\Yoti\SessionConfig;
use Application\Yoti\SessionStatusService;
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
        private readonly SessionStatusService $sessionService,
        private readonly SessionConfig $sessionConfig
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
                    "post_code" => $branch["post_code"]
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
        $case = $this->dataQuery->getCaseByUUID($uuid);
        $response = [];

        if (! $case) {
            $status = Response::STATUS_CODE_400;
            $this->getResponse()->setStatusCode($status);
            $response = [
                "error" => "Unable to locate case"
            ];
            return new JsonModel($response);
        }

        //start Yoti process
        $notificationsAuthToken = strval(Uuid::uuid4());

        $sessionData = $this->sessionConfig->build($case, $notificationsAuthToken);
        $nonce = strval(Uuid::uuid4());
        $dateTime = new DateTime();
        $timestamp = $dateTime->getTimestamp();

        try {
            $result = $this->yotiService->createSession($sessionData, $nonce, $timestamp);
            $yotiSessionId = $result["data"]["session_id"];

            if ($case->counterService !== null) {
                $case->counterService->notificationsAuthToken = $notificationsAuthToken;
            }

            if ($result["status"] < 400) {
                $this->dataImportHandler->updateCaseChildAttribute(
                    $uuid,
                    'counterService.notificationsAuthToken',
                    'S',
                    $notificationsAuthToken
                );

                $this->dataImportHandler->updateCaseData(
                    $uuid,
                    'yotiSessionId',
                    'S',
                    $yotiSessionId
                );
            }
            //Prepare and generate PDF
            $this->yotiService->preparePDFLetter($case, $nonce, $timestamp, $yotiSessionId);
            $pdf = $this->yotiService->retrieveLetterPDF($yotiSessionId, $nonce, $timestamp);
        } catch (YotiException $e) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_500);
            return new JsonModel(new Problem(
                'Problem requesting Yoti API',
                extra: ['errors' => $e->getMessage()],
            ));
        }

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);
        $response['result'] = "Session created";
        $response['pdfBase64'] = $pdf['pdfBase64'];

        return new JsonModel($response);
    }

    public function getSessionStatusAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');

        if (! $uuid) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(['error' => 'Missing uuid']);
        }

        $caseData = $this->dataQuery->getCaseByUUID($uuid);
        if (! $caseData) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(['error' => 'Case not found']);
        }

        $sessionId = $caseData->yotiSessionId;
        if ($sessionId === '00000000-0000-0000-0000-000000000000') {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(new Problem('SessionId not available'));
        }

        $sessionResult = $this->sessionService->getSessionStatus($caseData);

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);
        $data = ['results' => $sessionResult];

        return new JsonModel($data);
    }

    /**
     * @return JsonModel
     * @psalm-suppress PossiblyInvalidMethodCall, PossiblyUndefinedMethod, PossiblyNullPropertyFetch
     */
    public function notificationAction(): JsonModel
    {
        $authorization = $this->getRequest()->getHeaders()->get('authorization');

        if (preg_match('/Bearer\s+(\S+)/', $authorization->toString(), $matches)) {
            $token = $matches[1];
        } else {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_401);
            return new JsonModel(new Problem('Missing authorisation'));
        }

        $data = json_decode($this->getRequest()->getContent(), true);
        if (! isset($data['session_id'], $data['topic'])) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(new Problem('Missing required parameters'));
        }

        if (! in_array($data['topic'], ['first_branch_visit', 'session_completion'])) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(new Problem('Invalid type'));
        }

        if ($this->isValidUUID($data['session_id'])) {
            $caseData = $this->dataQuery->queryByYotiSessionId($data['session_id']);

            if (! $caseData) {
                $this->getResponse()->setStatusCode(Response::STATUS_CODE_500);
                return new JsonModel(new Problem('Case with session_id not found'));
            }
            //authorize
            if ($caseData->counterService->notificationsAuthToken === $token) {
                //now update counterService data
                $this->dataImportHandler->updateCaseChildAttribute(
                    $caseData->id,
                    'counterService.notificationState',
                    'S',
                    $data['topic'],
                );
                $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);
                return new JsonModel(["Notification Status" => "Updated"]);
            } else {
                $this->getResponse()->setStatusCode(Response::STATUS_CODE_403);
                return new JsonModel(new Problem('Unauthorised request'));
            }
        } else {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(new Problem('session_id provided is not a valid UUID'));
        }
    }
    public function isValidUUID(string $uuid): bool
    {
        // Regular expression pattern to match
        $pattern = '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/';
        // Use preg_match to check if the uuid matches the pattern
        return preg_match($pattern, $uuid) === 1;
    }

    public function estimatePostOfficeDeadlineAction(): JsonModel
    {
        return new JsonModel(['deadline' => $this->sessionConfig->deadlineDate()]);
    }
}
