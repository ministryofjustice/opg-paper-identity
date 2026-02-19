<?php

declare(strict_types=1);

namespace Application\Handler\Kbv;

use Application\Contracts\OpgApiServiceInterface;
use Application\Enums\PersonType;
use Application\Exceptions\HttpException;
use Application\Helpers\RouteHelper;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\Element;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilter;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @psalm-import-type Question from OpgApiServiceInterface
 */
class QuestionsHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly RouteHelper $routeHelper,
        private readonly TemplateRendererInterface $renderer,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $failRoute = "identity_check_failed";
        $passRoute = [
            PersonType::Donor->value => "identity_check_passed",
            PersonType::CertificateProvider->value => "cp_identity_check_passed",
            PersonType::Voucher->value => "voucher_identity_check_passed",
        ];

        $uuid = $request->getAttribute('uuid');
        $detailsData = $this->opgApiService->getDetailsData($uuid);


        $variables = ['uuid' => $uuid, 'details_data' => $detailsData];

        if (isset($detailsData['identityCheckPassed'])) {
            $siriusUrl = $this->routeHelper->getSiriusPublicUrl() . '/lpa/frontend/lpa/' . $detailsData['lpas'][0];

            return new HtmlResponse($this->renderer->render(
                'application/pages/cannot_start',
                [
                    ...$variables,
                    'message' => 'The identity check has already been completed',
                    'sirius_url' => $siriusUrl,
                ]
            ));
        }

        $questionsData = $this->opgApiService->getIdCheckQuestions($uuid);

        /**
         * @psalm-suppress PossiblyInvalidArrayAccess
         */
        $firstQuestion = $questionsData[0];
        $variables['first_question'] = $firstQuestion['question'];

        if ($questionsData === false) {
            throw new HttpException(500, 'Could not load KBV questions');
        }

        if (count($questionsData) === 0) {
            return $this->routeHelper->toRedirect('thin_file_failure', ['uuid' => $uuid]);
        }

        $questionsData = array_filter($questionsData, fn (array $question) => $question['answered'] !== true);

        $form = new Form();
        $inputFilter = new InputFilter();
        $form->setInputFilter($inputFilter);

        foreach ($questionsData as $question) {
            $form->add(new Element($question['externalId']));

            $inputFilter->add([
                'name' => $question['externalId'],
                'required' => true,
            ]);
        }

        $variables['questions_data'] = $questionsData;

        $formData = (array)($request->getParsedBody());
        $form->setData($formData);

        $nextQuestion = $this->getNextQuestion($questionsData, $formData);
        $variables['form_valid'] = $form->isValid();
        $variables['question'] = $nextQuestion;

        if ($request->getMethod() === 'GET') {
            $variables['form_valid'] = true;
        }

        // this check look weird, but it works for preventing a spurious form error on page 2
        foreach ($formData as $postVar) {
            if (
                (is_null($nextQuestion) || $firstQuestion['question'] !== $nextQuestion['question']) &&
                    $postVar === ""
            ) {
                $variables['form_valid'] = true;
            }
        }

        if (count($formData) > 0 && $nextQuestion === null) {
            $check = $this->opgApiService->checkIdCheckAnswers($uuid, ['answers' => $formData]);

            if (! $check['complete']) {
                return new RedirectResponse($request->getUri()->getPath());
            }

            if ($check['passed'] === true) {
                return $this->routeHelper->toRedirect($passRoute[$detailsData['personType']->value], ['uuid' => $uuid]);
            }

            return $this->routeHelper->toRedirect($failRoute, ['uuid' => $uuid]);
        }

        $variables['form'] = $form;

        return new HtmlResponse($this->renderer->render(
            'application/pages/identity_check_questions',
            $variables
        ));
    }

    /**
     * @template Question of array
     * @param Question[] $questions
     * @return ?Question
     */
    private function getNextQuestion(array $questions, array $formData): ?array
    {
        foreach ($questions as $question) {
            if (empty($formData[$question['externalId']])) {
                return $question;
            }
        }

        return null;
    }
}
