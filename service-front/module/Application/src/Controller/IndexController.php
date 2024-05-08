<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Exceptions\OpgApiException;
use Application\Forms\DrivingLicenceNumber;
use Application\Forms\IdQuestions;
use Application\Forms\PassportNumber;
use Application\Forms\PassportDate;
use Application\Services\FormProcessorService;
use Application\Services\SiriusApiService;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\Controller\Plugin\Redirect;
use Laminas\View\Model\ViewModel;
use Laminas\Form\Annotation\AttributeBuilder;
use Application\Forms\NationalInsuranceNumber;

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

    public function startAction(): Response
    {
        $lpasQuery = $this->params()->fromQuery("lpas");
        $lpas = [];
        foreach ($lpasQuery as $lpaUid) {
            $data = $this->siriusApiService->getLpaByUid($lpaUid, $this->getRequest());
            $lpas[] = $data['opg.poas.lpastore'];
        }

        $type = $this->params()->fromQuery("personType");
        $detailsData = $this->processLpaResponse($type, $lpas[0]);

        die(json_encode($lpas));

        $firstName = $detailsData['FirstName'];
        $lastName = $detailsData['LastName'];

        $dob = (new \DateTime($detailsData['DOB']))->format("Y-m-d");
        $address = $detailsData['Address'];
        // Find the details of the actor (donor or certificate provider, based on URL) that we need to ID check them

        // Create a case in the API with the LPA UID and the actors' details

        // Redirect to the "select which ID to use" page for this case

        $case = $this->opgApiService->createCase($firstName, $lastName, $dob, $type, $lpasQuery, $address);

        return $type === 'donor' ?
            $this->redirect()->toRoute('how_donor_confirms', ['uuid' => $case['uuid']]) :
            $this->redirect()->toRoute('how_cp_confirms', ['uuid' => $case['uuid']]);
    }

    private function processLpaResponse(string $type, array $data): array
    {
        $parsedIdentity = [];

        if ($type === 'donor') {
            $parsedIdentity['FirstName'] = $data['donor']['firstNames'];
            $parsedIdentity['LastName'] = $data['donor']['lastName'];
            $parsedIdentity['dob'] = (new \DateTime($data['donor']['dateOfBirth']))->format("Y-m-d");
            $parsedIdentity['Address'] = $data['donor']['address'];
        } else {
            $parsedIdentity['FirstName'] = $data['certificateProvider']['firstNames'];
            $parsedIdentity['LastName'] = $data['certificateProvider']['lastName'];
            $parsedIdentity['Address'] = $data['certificateProvider']['address'];
        }

        return $parsedIdentity;
    }
}
