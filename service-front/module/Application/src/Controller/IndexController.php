<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Exceptions\HttpException;
use Application\Forms\AbandonFlow;
use Application\Helpers\AddressProcessorHelper;
use Application\Helpers\LpaFormHelper;
use Application\Services\SiriusApiService;
use Laminas\Form\Annotation\AttributeBuilder;
use DateTime;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

/**
 * @psalm-import-type Lpa from SiriusApiService
 * @psalm-import-type Address from SiriusApiService
 *
 * @psalm-type Identity array{first_name: string, last_name: string, dob: string, address: string[]}
 */
class IndexController extends AbstractActionController
{
    protected $plugins;

    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly SiriusApiService $siriusApiService,
        private readonly LpaFormHelper $lpaFormHelper
    ) {
    }

    public function indexAction()
    {
        $this->opgApiService->ping();

        return new ViewModel();
    }

    public function startAction(): Response
    {
        /** @var string[] $lpasQuery */
        $lpasQuery = $this->params()->fromQuery("lpas");
        $lpas = [];
        foreach ($lpasQuery as $lpaUid) {
            $data = $this->siriusApiService->getLpaByUid($lpaUid, $this->getRequest());
            $lpas[] = $data;
        }

        /** @var string $type */
        $type = $this->params()->fromQuery("personType");

        if (! $this->lpaFormHelper->lpaIdentitiesMatch($lpas, $type)) {
            $personTypeDescription = $type === 'donor' ? "Donors" : " Certificate Providers";
            throw new HttpException(400, "These LPAs are for different " . $personTypeDescription);
        }
        /**
         * @psalm-suppress PossiblyUndefinedArrayOffset
         */
        $detailsData = $this->processLpaResponse($type, $lpas[0]);

        $case = $this->opgApiService->createCase(
            $detailsData['first_name'],
            $detailsData['last_name'],
            $detailsData['dob'],
            $type,
            $lpasQuery,
            $detailsData['address']
        );

        return $type === 'donor' ?
            $this->redirect()->toRoute('root/how_donor_confirms', ['uuid' => $case['uuid']]) :
            $this->redirect()->toRoute('root/cp_how_cp_confirms', ['uuid' => $case['uuid']]);
    }

    /**
     * @param Lpa $data
     * @return Identity
     */
    private function processLpaResponse(string $type, array $data): array
    {
        if ($type === 'donor') {
            if (! empty($data['opg.poas.lpastore'])) {
                $address = (new AddressProcessorHelper())->processAddress(
                    $data['opg.poas.lpastore']['donor']['address'],
                    'lpaStoreAddressType'
                );

                return [
                    'first_name' => $data['opg.poas.lpastore']['donor']['firstNames'],
                    'last_name' => $data['opg.poas.lpastore']['donor']['lastName'],
                    'dob' => (new DateTime($data['opg.poas.lpastore']['donor']['dateOfBirth']))->format("Y-m-d"),
                    'address' => $address,
                ];
            }

            $address = (new AddressProcessorHelper())->processAddress(
                $data['opg.poas.sirius']['donor'],
                'siriusAddressType'
            );

            return [
                'first_name' => $data['opg.poas.sirius']['donor']['firstname'],
                'last_name' => $data['opg.poas.sirius']['donor']['surname'],
                'dob' => (new DateTime($data['opg.poas.sirius']['donor']['dob']))->format("Y-m-d"),
                'address' => $address,
            ];
        } elseif ($type === 'certificateProvider') {
            if ($data['opg.poas.lpastore'] === null) {
                throw new HttpException(
                    400,
                    'Cannot ID check this certificate provider as the LPA has not yet been submitted',
                );
            }

            $address = (new AddressProcessorHelper())->processAddress(
                $data['opg.poas.lpastore']['certificateProvider']['address'],
                'lpaStoreAddressType'
            );

            return [
                'first_name' => $data['opg.poas.lpastore']['certificateProvider']['firstNames'],
                'last_name' => $data['opg.poas.lpastore']['certificateProvider']['lastName'],
                'dob' => '1000-01-01', //temp setting should be null in prod
                'address' => $address,
            ];
        }

        throw new HttpException(400, 'Person type "' . $type . '" is not valid');
    }

    public function abandonFlowAction(): ViewModel
    {
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $form = (new AttributeBuilder())->createForm(AbandonFlow::class);
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        if (count($this->getRequest()->getPost())) {
            $formData = $this->getRequest()->getPost();
            $form->setData($formData);
            if ($form->isValid()) {
                $siriusData = [
                    "reference" => $uuid,
                    "actorType" => $detailsData['personType'],
                    "lpaIds" => $detailsData['lpas'],
                    "time" => (new \DateTime('NOW'))->format('c'),
                    "outcome" => "exit"
                ];

                $this->siriusApiService->abandonCase($siriusData, $this->getRequest());
            }
//            $this->redirect()->toRoute();
        }

        $lastPage = $this->getRequest()->getQuery('last_page');

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('last_page', $lastPage);
        $view->setVariable('form', $form);


        return $view->setTemplate('application/pages/abandoned_flow');
    }
}
