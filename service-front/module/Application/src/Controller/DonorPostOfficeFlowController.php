<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Services\FormProcessorService;
use Application\Services\SiriusApiService;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\Controller\Plugin\Redirect;
use Laminas\View\Model\ViewModel;
use Laminas\Form\Annotation\AttributeBuilder;

class DonorPostOfficeFlowController extends AbstractActionController
{
    protected $plugins;

    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        //        private readonly FormProcessorService $formProcessorService,
        private readonly array $config,
    ) {
    }

    public function postOfficeDocumentsAction(): ViewModel|Response
    {
        $uuid = $this->params()->fromRoute("uuid");

        if (count($this->getRequest()->getPost())) {
            $formData = $this->getRequest()->getPost()->toArray();
            $response = $this->opgApiService->updateIdMethod($uuid, $formData['id_method']);

            if ($response === "Updated") {
                return $this->redirect()->toRoute("donor_details_match_check", ['uuid' => $uuid]);
            }
        }

        $optionsdata = $this->config['opg_settings']['post_office_identity_methods'];
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $view = new ViewModel();

        $view->setVariable('options_data', $optionsdata);
        $view->setVariable('details_data', $detailsData);
        $view->setVariable('uuid', $uuid);

        return $view->setTemplate('application/pages/post_office/post_office_documents');
    }

    public function findPostOfficeAction(): ViewModel|Response
    {
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");

        $optionsdata = $this->config['opg_settings']['post_office_identity_methods'];
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        if (count($this->getRequest()->getPost())) {
            $formData = $this->getRequest()->getPost()->toArray();
            $view->setVariable('next_page', $formData['next_page']);

            if ($formData['next_page'] == '2') {
                if ($formData['postcode'] == 'alt') {
                    $postcode = $formData['alt_postcode'];
                } else {
                    $postcode = $formData['postcode'];
                }

                $response = $this->opgApiService->listPostOfficesByPostcode($uuid, $postcode);

                $view->setVariable('post_office_list', $response);
            } elseif ($formData['next_page'] == '3') {
                $date = new \DateTime();
                $date->modify("+90 days");
                $deadline = $date->format("d M Y");

                $postOfficeData = $this->opgApiService->getPostOfficeByCode($uuid, $formData['postoffice']);

                /**
                 * @psalm-suppress PossiblyInvalidArrayAccess
                 */
                $postOfficeAddress = explode(",", $postOfficeData['address']);

                $view->setVariable('post_office_summary', true);
                $view->setVariable('post_office_data', $postOfficeData);
                $view->setVariable('post_office_address', $postOfficeAddress);
                $view->setVariable('deadline', $deadline);
                $view->setVariable('id_method', $optionsdata[$detailsData['idMethod']]);
            }
        }

        $optionsdata = $this->config['opg_settings']['post_office_identity_methods'];
        $postcode = "";
        foreach ($detailsData['address'] as $line) {
            if (preg_match('/^[A-Z]{1,2}[0-9]{1,2}[A-Z]? [0-9][A-Z]{2}$/', $line)) {
                $postcode = $line;
            }
        }

        $view->setVariable('postcode', $postcode);
        $view->setVariable('options_data', $optionsdata);
        $view->setVariable('details_data', $detailsData);
        $view->setVariable('uuid', $uuid);

        return $view->setTemplate('application/pages/post_office/find_post_office');
    }

    public function whatHappensNextAction(): ViewModel
    {
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $view->setVariable('details_data', $detailsData);
        return $view->setTemplate('application/pages/post_office/what_happens_next');
    }

    public function postOfficeRouteNotAvailableAction(): ViewModel
    {
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $view->setVariable('details_data', $detailsData);
        return $view->setTemplate('application/pages/post_office/post_office_route_not_available');
    }
}
