<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Enums\IdMethod;
use Application\Forms\IdMethod as IdMethodForm;
use Application\Forms\PassportDate;
use Application\Helpers\FormProcessorHelper;
use Application\PostOffice\Country;
use Laminas\Http\Response;
use Laminas\Form\FormInterface;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class HowConfirmController extends AbstractActionController
{
    use FormBuilder;

    protected $plugins;

    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly FormProcessorHelper $formProcessorHelper,
        private readonly array $config,
    ) {
    }

    public function howWillYouConfirmAction(): ViewModel|Response
    {
        $templates = ['default' => 'application/pages/how_will_you_confirm'];
        $uuid = $this->params()->fromRoute("uuid");
        $view = new ViewModel();
        $dateSubForm = $this->createForm(PassportDate::class);
        $form = $this->createForm(IdMethodForm::class);

        $serviceAvailability = $this->opgApiService->getServiceAvailability($uuid);

        $identityDocs = [];
        foreach ($this->config['opg_settings']['identity_documents'] as $key => $value) {
            if ($serviceAvailability['data'][$key] === true) {
                $identityDocs[$key] = $value;
            }
        }

        $methods = [];
        foreach (array_keys($this->config['opg_settings']['identity_methods']) as $key) {
            if (array_key_exists($key, $serviceAvailability['data'])) {
                /**
                * @psalm-suppress InvalidArrayOffset
                */
                $methods[$key] = $serviceAvailability['data'][$key];
            } else {
                /**
                * @psalm-suppress InvalidArrayOffset
                */
                $methods[$key] = true;
            }
        }

        $detailsData = $this->opgApiService->getDetailsData($uuid);

        echo json_encode($detailsData);

        $view->setVariable('date_sub_form', $dateSubForm);
        $view->setVariable('form', $form);
        $view->setVariable('options_data', $identityDocs);
        $view->setVariable('methods_data', $methods);
        $view->setVariable('service_availability', $serviceAvailability);
        $view->setVariable('details_data', $detailsData);
        $view->setVariable('uuid', $uuid);

        if ($this->getRequest()->isPost()) {
            $formData = $this->getRequest()->getPost()->toArray();
            if (array_key_exists('check_button', $formData)) {
                $variables = $this->handlePassportDateCheckFormSubmission($dateSubForm, $templates, $uuid);
                $view->setVariables($variables);
            } else {
                $response = $this->handleIdMethodFormSubmission($form, $formData, $uuid, $detailsData['personType']);
                if ($response) {
                    return $response;
                }
            }
        }

        return $view->setTemplate($templates['default']);
    }

    /**
    * @param FormInterface $idMethodForm
    * @param array<string, mixed> $formData
    * @return Response|null
    */
    private function handleIdMethodFormSubmission(
        FormInterface $idMethodForm,
        array $formData,
        string $uuid,
        string $personType
    ): Response|null {
        $routes = [
            'donor' => 'root/donor_details_match_check',
            'certificateProvider' => 'root/cp_name_match_check',
            'voucher' => 'root/voucher_name',
        ];

        if (! $idMethodForm->isValid()) {
            return null;
        }

        if ($formData['id_method'] == IdMethod::PostOffice->value) {
            $data = ['id_route' => IdMethod::PostOffice->value];
            $returnRoute = 'root/post_office_documents';
        } elseif ($formData['id_method'] == IdMethod::OnBehalf->value) {
            $data = ['id_route' => IdMethod::OnBehalf->value];
            $returnRoute = "root/what_is_vouching";
        } elseif ($formData['id_method'] == IdMethod::CourtOfProtection->value) {
            $data = ['id_route' => IdMethod::CourtOfProtection->value];
            $returnRoute = "root/court_of_protection";
        } else {
            $data = [
                'id_route' => 'TELEPHONE',
                'id_country' => Country::GBR->value,
                'id_method' => $formData['id_method']
            ];
            $returnRoute = $routes[$personType];
        }
        $this->opgApiService->updateIdMethodWithCountry(
            $uuid,
            $data
        );
        return $this->redirect()->toRoute($returnRoute, ['uuid' => $uuid]);
    }

    /**
    * @param FormInterface $dateSubForm
    * @param array<string, mixed> $templates
    * @return array<string, mixed>
    */
    private function handlePassportDateCheckFormSubmission(
        FormInterface $dateSubForm,
        array $templates,
        string $uuid
    ): array {
        $formProcessorResponseDto = $this->formProcessorHelper->processPassportDateForm(
            $uuid,
            $this->getRequest()->getPost(),
            $dateSubForm,
            $templates
        );
        return $formProcessorResponseDto->getVariables();
    }
}
