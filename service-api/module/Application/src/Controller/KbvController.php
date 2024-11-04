<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Fixtures\DataQueryHandler;
use Application\Helpers\CaseOutcomeCalculator;
use Application\KBV\KBVServiceInterface;
use Application\Model\Entity\Problem;
use Application\View\JsonModel;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 * Needed here due to false positive from Laminas’s uninitialised properties
 * @psalm-suppress InvalidArgument
 * @see https://github.com/laminas/laminas-view/issues/239
 */
class KbvController extends AbstractActionController
{
    public function __construct(
        private readonly DataQueryHandler $dataQueryHandler,
        private readonly CaseOutcomeCalculator $caseOutcomeCalculator,
        private readonly KBVServiceInterface $KBVService,
    ) {
    }

    public function getQuestionsAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');

        if (! $uuid) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);

            return new JsonModel(new Problem('Missing UUID'));
        }

        $case = $this->dataQueryHandler->getCaseByUUID($uuid);

        if (is_null($case) || $case->documentComplete === false) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);
            $response = [
                "error" => "Document checks incomplete or unable to locate case",
            ];

            return new JsonModel($response);
        }

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);

        $questions = $this->KBVService->fetchFormattedQuestions($uuid);

        return new JsonModel($questions);
    }

    public function checkAnswersAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        $data = json_decode($this->getRequest()->getContent(), true);
        $case = $this->dataQueryHandler->getCaseByUUID($uuid);

        if (! $uuid || is_null($case)) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);

            return new JsonModel(new Problem("Missing UUID or unable to find case"));
        }

        $result = $this->KBVService->checkAnswers($data['answers'], $uuid);

        if ($result->isComplete()) {
            $response = [
                'complete' => true,
                'passed' => $result->isPass(),
            ];

            $case->identityCheckPassed = true;
            $this->caseOutcomeCalculator->updateSendIdentityCheck($case, date_create()->format('c'));
        } else {
            $response = [
                'complete' => false,
                'passed' => false,
            ];
        }


        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);

        return new JsonModel($response);
    }
}
