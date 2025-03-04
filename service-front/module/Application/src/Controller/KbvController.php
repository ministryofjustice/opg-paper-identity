<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Exceptions\HttpException;
use Laminas\Form\Element;
use Laminas\Form\Form;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Stdlib\Parameters;
use Laminas\View\Model\ViewModel;

/**
 * @psalm-import-type Question from OpgApiServiceInterface
 */
class KbvController extends AbstractActionController
{
    protected $plugins;

    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
    ) {
    }

    public function idVerifyQuestionsAction(): ViewModel|Response
    {
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $view->setVariable('uuid', $uuid);

        $detailsData = $this->opgApiService->getDetailsData($uuid);
        if ($detailsData['personType'] == 'certificateProvider') {
            $passRoute = "root/cp_identity_check_passed";
            $failRoute = "root/cp_identity_check_failed";
        } elseif ($detailsData['personType'] == 'voucher') {
            $passRoute = "root/voucher_identity_check_passed";
            $failRoute = "root/voucher_identity_check_failed";
        } else {
            $passRoute = "root/identity_check_passed";
            $failRoute = "root/identity_check_failed";
        }
        $view->setVariable('details_data', $detailsData);

        $questionsData = $this->opgApiService->getIdCheckQuestions($uuid);

        /**
         * @psalm-suppress PossiblyInvalidArrayAccess
         */
        $firstQuestion = $questionsData[0]['question'];
        $view->setVariable('first_question', $firstQuestion);

        if ($questionsData === false) {
            throw new HttpException(500, 'Could not load KBV questions');
        }

        if (count($questionsData) === 0) {
            return $this->redirect()->toRoute('root/thin_file_failure', ['uuid' => $uuid]);
        }

        $questionsData = array_filter($questionsData, fn (array $question) => $question['answered'] !== true);

        $form = new Form();

        foreach ($questionsData as $question) {
            $form->add(new Element($question['externalId']));
        }

        $view->setVariable('questions_data', $questionsData);

        $formData = $this->getRequest()->getPost();
        $nextQuestion = $this->getNextQuestion($questionsData, $formData);
        $view->setVariable('question', $nextQuestion);

        if (count($formData) > 0) {
            if ($nextQuestion === null) {
                $check = $this->opgApiService->checkIdCheckAnswers($uuid, ['answers' => $formData->toArray()]);

                if (! $check['complete']) {
                    return $this->redirect()->refresh();
                }

                if ($check['passed'] === true) {
                    $caseProgressData = $detailsData['caseProgress'] ?? [];
                    $caseProgressData['kbvs'] = [
                        'result' => true
                    ];
                    $this->opgApiService->updateCaseProgress($uuid, $caseProgressData);

                    return $this->redirect()->toRoute($passRoute, ['uuid' => $uuid]);
                }

                return $this->redirect()->toRoute($failRoute, ['uuid' => $uuid]);
            }
            $form->setData($formData);
        }
        $view->setVariable('form', $form);

        return $view->setTemplate('application/pages/identity_check_questions');
    }

    /**
     * @template Question of array
     * @param Question[] $questions
     * @return ?Question
     */
    private function getNextQuestion(array $questions, Parameters $formData): ?array
    {
        foreach ($questions as $question) {
            if (! $formData[$question['externalId']]) {
                return $question;
            }
        }

        return null;
    }
}
