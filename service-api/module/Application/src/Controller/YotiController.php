<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Fixtures\DataImportHandler;
use Application\Fixtures\DataQueryHandler;
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
     * @return JsonModel
     */
    public function notificationAction(): JsonModel
    {
        $data = json_decode($this->getRequest()->getContent(), true);
        if ( !isset($data['session_id'], $data['topic'])) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(new Problem('Missing required parameters'));
        }

        if ( !in_array($data['topic'], array('first_branch_visit', 'session_completion'))) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(new Problem('Invalid type'));
        }

        $sessionId = filter_var($data['session_id'], FILTER_SANITIZE_SPECIAL_CHARS);

        $caseData = $this->dataQuery->queryByYotiSessionId($sessionId);
        //now update counterService data
        $counterServiceMap = [];

        if ($caseData->counterService !== null) {
            $counterServiceMap["selectedPostOffice"] = $caseData->counterService->selectedPostOffice;
            $counterServiceMap["selectedPostOfficeDeadline"] =
                $caseData->counterService->selectedPostOfficeDeadline;
            $counterServiceMap["notificationsAuthToken"] =
                $caseData->counterService->notificationsAuthToken;
        }
        $counterServiceMap["notificationState"] = $data['topic'];
        $this->dataImportHandler->updateCaseData(
            $caseData->id,
            'counterService',
            'M',
            array_map(fn (mixed $v) => [
                'S' => $v
            ], $counterServiceMap),
        );

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);
        return new JsonModel(["Notification Status" => "Updated"]);
    }
}
