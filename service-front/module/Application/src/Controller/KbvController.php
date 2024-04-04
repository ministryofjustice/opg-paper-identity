<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Exceptions\OpgApiException;
use Application\Forms\IdQuestions;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Form\Annotation\AttributeBuilder;

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

        $form = (new AttributeBuilder())->createForm(IdQuestions::class);
        $questionsData = $this->opgApiService->getIdCheckQuestions($uuid);

        if (array_key_exists('error', $questionsData)) {
            return $this->redirect()->toRoute('thin_file_failure', ['uuid' => $uuid]);
        }

        $view->setVariable('questions_data', $questionsData);

        $view->setVariable('question', 'one');

        if (count($this->getRequest()->getPost())) {
            $formData = $this->getRequest()->getPost();

            $next = $this->getNextQuestion($formData->toArray());

            if ($next != 'end') {
                $view->setVariable('question', $next);
            } else {
                try {
                    $check = $this->opgApiService->checkIdCheckAnswers($uuid, ['answers' => $formData->toArray()]);

                    if (! $check) {
                        return $this->redirect()->toRoute('identity_check_failed', ['uuid' => $uuid]);
                    }

                    return $this->redirect()->toRoute('identity_check_passed', ['uuid' => $uuid]);
                } catch (OpgApiException $exception) {
                    return $this->redirect()->toRoute('identity_check_failed', ['uuid' => $uuid]);
                }
            }
            $form->setData($formData);
        }
        $view->setVariable('form', $form);

        return $view->setTemplate('application/pages/identity_check_questions');
    }

    private function getNextQuestion(array $formdata): string
    {
        $question = null;
        foreach ($formdata as $key => $value) {
            if (strlen($value) == 0) {
                continue;
            } else {
                $question = $key;
            }
        }

        $sequence = [
            "one" => "two",
            "two" => "three",
            "three" => "four",
            "four" => "end"
        ];

        return $sequence[$question] ?? "";
    }
}
