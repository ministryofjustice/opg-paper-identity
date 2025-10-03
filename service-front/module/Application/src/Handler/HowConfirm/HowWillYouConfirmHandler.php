<?php

declare(strict_types=1);

namespace Application\Handler\HowConfirm;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Enums\IdRoute;
use Application\Enums\PersonType;
use Application\Forms\IdMethod;
use Application\Forms\PassportDate;
use Application\Helpers\FormProcessorHelper;
use Application\Helpers\RouteHelper;
use Application\PostOffice\Country;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Form\FormInterface;
use Laminas\Stdlib\Parameters;
use Laminas\View\Model\ViewModel;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class HowWillYouConfirmHandler implements RequestHandlerInterface
{
    use FormBuilder;

    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly FormProcessorHelper $formProcessorHelper,
        private readonly RouteHelper $routeHelper,
        private readonly TemplateRendererInterface $renderer,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $templates = ['default' => 'application/pages/how_will_you_confirm'];
        $uuid = $request->getAttribute('uuid');

        $view = new ViewModel();
        $formData = (array)($request->getParsedBody());
        $dateSubForm = $this->createForm(PassportDate::class, $formData);
        $form = $this->createForm(IdMethod::class, $formData);

        $routeAvailability = $this->opgApiService->getRouteAvailability($uuid);

        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $view->setVariable('date_sub_form', $dateSubForm);
        $view->setVariable('form', $form);
        $view->setVariable('route_availability', $routeAvailability);
        $view->setVariable('details_data', $detailsData);
        $view->setVariable('uuid', $uuid);

        if ($request->getMethod() === 'POST') {
            if (array_key_exists('check_button', $formData)) {
                $variables = $this->handlePassportDateCheckFormSubmission($dateSubForm, $formData, $templates, $uuid);
                $view->setVariables($variables);
            } else {
                $response = $this->handleIdMethodFormSubmission($form, $formData, $uuid, $detailsData['personType']);
                if ($response) {
                    return $response;
                }
            }
        }

        return new HtmlResponse($this->renderer->render($templates['default'], $view->getVariables()));
    }

    /**
    * @param array<string, mixed> $formData
    */
    private function handleIdMethodFormSubmission(
        FormInterface $idMethodForm,
        array $formData,
        string $uuid,
        PersonType $personType
    ): ResponseInterface|null {
        $routes = [
            PersonType::Donor->value => 'root/donor_details_match_check',
            PersonType::CertificateProvider->value => 'root/cp_name_match_check',
            PersonType::Voucher->value => 'root/voucher_name',
        ];

        if (! $idMethodForm->isValid()) {
            return null;
        }

        if ($formData['id_method'] == IdRoute::POST_OFFICE->value) {
            $idMethod = ['idRoute' => IdRoute::POST_OFFICE->value];
            $returnRoute = 'root/post_office_documents';
        } elseif ($formData['id_method'] == IdRoute::VOUCHING->value) {
            $idMethod = ['idRoute' => IdRoute::VOUCHING->value];
            $returnRoute = "root/what_is_vouching";
        } elseif ($formData['id_method'] == IdRoute::COURT_OF_PROTECTION->value) {
            $idMethod = ['idRoute' => IdRoute::COURT_OF_PROTECTION->value];
            $returnRoute = "root/court_of_protection";
        } else {
            $idMethod = [
                'idRoute' => IdRoute::KBV->value,
                'idCountry' => Country::GBR->value,
                'docType' => $formData['id_method'],
            ];
            $returnRoute = $routes[$personType->value];
        }
        $this->opgApiService->updateIdMethod($uuid, $idMethod);

        return $this->routeHelper->toRedirect($returnRoute, ['uuid' => $uuid]);
    }

    /**
    * @param array<array-key, mixed> $formData
    * @param array<string, mixed> $templates
    * @return array<string, mixed>
    */
    private function handlePassportDateCheckFormSubmission(
        FormInterface $dateSubForm,
        array $formData,
        array $templates,
        string $uuid
    ): array {
        $formProcessorResponseDto = $this->formProcessorHelper->processPassportDateForm(
            $uuid,
            new Parameters($formData),
            $dateSubForm,
            $templates
        );

        return $formProcessorResponseDto->getVariables();
    }
}
