<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Services\SiriusApiService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    protected $plugins;

    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly SiriusApiService $siriusApiService,
    ) {
    }

    public function indexAction()
    {
        return new ViewModel();
    }

    public function startAction()
    {
        $lpas = [];
        foreach ($this->params()->fromQuery("lpas") as $lpaUid) {
            $data = $this->siriusApiService->getLpaByUid($lpaUid, $this->getRequest());
            $lpas[] = $data['opg.poas.lpastore'];
        }

        // Find the details of the actor (donor or certificate provider, based on URL) that we need to ID check them

        // Create a case in the API with the LPA UID and the actors' details

        // Redirect to the "select which ID to use" page for this case

        return new ViewModel([
            'lpaUids' => $this->params()->fromQuery("lpas"),
            'type' => $this->params()->fromQuery("personType"),
            'lpas' => $lpas,
        ]);
    }

    public function donorIdCheckAction(): ViewModel
    {
        $optionsdata = $this->opgApiService->getIdOptionsData();
        $detailsData = $this->opgApiService->getDetailsData();

        $view = new ViewModel();

        $view->setVariable('options_data', $optionsdata);
        $view->setVariable('details_data', $detailsData);

        return $view->setTemplate('application/pages/donor_id_check');
    }

    public function donorLpaCheckAction(): ViewModel
    {
        $data = $this->opgApiService->getLpasByDonorData();

        $view = new ViewModel();

        $view->setVariable('data', $data);

        return $view->setTemplate('application/pages/donor_lpa_check');
    }

    public function addressVerificationAction(): ViewModel
    {
        $data = $this->opgApiService->getAddressVerificationData();

        $view = new ViewModel();

        $view->setVariable('options_data', $data);

        return $view->setTemplate('application/pages/address_verification');
    }
}
