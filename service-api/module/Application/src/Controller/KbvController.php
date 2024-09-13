<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Fixtures\DataQueryHandler;
use Application\Fixtures\DataWriteHandler;
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
        private readonly DataWriteHandler $dataWriteHandler,
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

        if (! is_null($case->kbvQuestions)) {
            $questions = json_decode($case->kbvQuestions, true);

            //revisit formatting here, special character outputs
            return new JsonModel($questions);
        }

        $questions = $this->KBVService->fetchFormattedQuestions($uuid);

        $this->dataWriteHandler->updateCaseData(
            $uuid,
            'kbvQuestions',
            'S',
            json_encode($questions)
        );

        return new JsonModel($questions);
    }

    public function checkAnswersAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        $data = json_decode($this->getRequest()->getContent(), true);
        $case = $this->dataQueryHandler->getCaseByUUID($uuid);

        $result = 'pass';
        $response = [];

        if (! $uuid || is_null($case)) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);

            return new JsonModel(new Problem("Missing UUID or unable to find case"));
        }

        $questions = json_decode($case->kbvQuestions, true);
        //compare against all stored answers to ensure all answers passed
        foreach ($questions as $key => $question) {
            if (! isset($data['answers'][$key])) {
                $result = 'fail';
            } elseif ($data['answers'][$key] != $question['answer']) {
                $result = 'fail';
            }
        }

        $response['result'] = $result;

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);

        return new JsonModel($response);
    }
}
