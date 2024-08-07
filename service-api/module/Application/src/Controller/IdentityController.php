<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Aws\Secrets\AwsSecret;
use Application\Nino\ValidatorInterface;
use Application\DrivingLicense\ValidatorInterface as LicenseValidatorInterface;
use Application\Passport\ValidatorInterface as PassportValidator;
use Application\KBV\KBVServiceInterface;
use Application\Fixtures\DataImportHandler;
use Application\Fixtures\DataQueryHandler;
use Application\Model\Entity\CaseData;
use Application\Model\Entity\Problem;
use Application\View\JsonModel;
use Application\Yoti\Http\Exception\YotiException;
use Application\Yoti\SessionConfig;
use Application\Yoti\YotiServiceInterface;
use DateTime;
use Laminas\Cache\Storage\PluginManager;
use Laminas\Form\Annotation\AttributeBuilder;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 * Needed here due to false positive from Laminas’s uninitialised properties
 * @psalm-suppress InvalidArgument
 * @see https://github.com/laminas/laminas-view/issues/239
 */
class IdentityController extends AbstractActionController
{
    public function __construct(
        private readonly ValidatorInterface $ninoService,
        private readonly DataQueryHandler $dataQueryHandler,
        private readonly DataImportHandler $dataImportHandler,
        private readonly LicenseValidatorInterface $licenseValidator,
        private readonly PassportValidator $passportService,
        private readonly KBVServiceInterface $KBVService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function indexAction(): JsonModel
    {
        return new JsonModel();
    }

    public function createAction(): JsonModel
    {
        $data = json_decode($this->getRequest()->getContent(), true);

        $caseData = CaseData::fromArray($data);

        $validator = (new AttributeBuilder())
            ->createForm($caseData)
            ->setData(get_object_vars($caseData));

        if ($validator->isValid()) {
            $caseData->id = strval(Uuid::uuid4());

            try {
                $this->dataImportHandler->insertData($caseData);
            } catch (\Exception $exception) {
                $this->getResponse()->setStatusCode(Response::STATUS_CODE_500);
                return new JsonModel(new Problem($exception->getMessage()));
            }

            $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);
            return new JsonModel(['uuid' => $caseData->id]);
        }

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);

        return new JsonModel(new Problem(
            'Invalid data',
            extra: ['errors' => $validator->getMessages()],
        ));
    }

    public function detailsAction(): JsonModel
    {
        /** @var string $uuid */
        $uuid = $this->getRequest()->getQuery('uuid');

        if (! $uuid) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(new Problem('Missing uuid'));
        }

        $case = $this->dataQueryHandler->getCaseByUUID($uuid);
        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);

        if (! empty($case)) {
            return new JsonModel($case);
        }

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_404);
        return new JsonModel(new Problem('Case not found'));
    }

    public function findByNameAction(): JsonModel
    {
        /** @var string $name */
        $name = $this->getRequest()->getQuery('username');
        $data = $this->dataQueryHandler->queryByName($name);
        /**
         * @psalm-suppress InvalidArgument
         * @see https://github.com/laminas/laminas-view/issues/239
         */
        return new JsonModel($data);
    }

    public function findByIdNumberAction(): JsonModel
    {
        /** @var string $id */
        $id = $this->getRequest()->getQuery('id');
        $data = $this->dataQueryHandler->queryByIDNumber($id);
        /**
         * @psalm-suppress InvalidArgument
         * @see https://github.com/laminas/laminas-view/issues/239
         */
        return new JsonModel($data);
    }

    public function addressVerificationAction(): JsonModel
    {
        $data = [
            'Passport',
            'Driving Licence',
            'National Insurance Number',
            'Voucher',
            'Post Office',
        ];

        return new JsonModel($data);
    }

    public function listLpasAction(): JsonModel
    {
        $data = [
            [
                'lpa_ref' => 'PW PA M-XYXY-YAGA-35G3',
                'donor_name' => 'Mary Anne Chapman'
            ],
            [
                'lpa_ref' => 'PW M-VGAS-OAGA-34G9',
                'donor_name' => 'Mary Anne Chapman'
            ]
        ];

        return new JsonModel($data);
    }

    public function verifyNinoAction(): JsonModel
    {
        $data = json_decode($this->getRequest()->getContent(), true);

        $ninoStatus = $this->ninoService->validateNINO($data['nino']);

        $response = [
            'status' => $ninoStatus
        ];

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);

        return new JsonModel($response);
    }

    public function validateDrivingLicenceAction(): JsonModel
    {
        $data = json_decode($this->getRequest()->getContent(), true);
        $licenseStatus = $this->licenseValidator->validateDrivingLicense($data['dln']);

        $response = [
            'status' => $licenseStatus
        ];
        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);

        return new JsonModel($response);
    }

    public function validatePassportAction(): JsonModel
    {
        $data = json_decode($this->getRequest()->getContent(), true);
        $passportStatus = $this->passportService->validatePassport(intval($data['passport']));

        $response = [
            'status' => $passportStatus
        ];
        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);

        return new JsonModel($response);
    }

    public function getKbvQuestionsAction(): JsonModel
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
                "error" => "Document checks incomplete or unable to locate case"
            ];
            return new JsonModel($response);
        }

        $questionsWithoutAnswers = [];

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);

        if (! is_null($case->kbvQuestions)) {
            $questions = json_decode($case->kbvQuestions, true);

            foreach ($questions as $number => $question) {
                unset($question['answer']);
                $questionsWithoutAnswers[$number] = $question;
            }
            //revisit formatting here, special character outputs
            return new JsonModel($questionsWithoutAnswers);
        } else {
            $questions = $this->KBVService->fetchFormattedQuestions($uuid);

            $this->dataImportHandler->updateCaseData(
                $uuid,
                'kbvQuestions',
                'S',
                json_encode($questions['formattedQuestions'])
            );
        }

        return new JsonModel($questions['questionsWithoutAnswers']);
    }

    public function checkKbvAnswersAction(): JsonModel
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

    public function updatedMethodAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        $data = json_decode($this->getRequest()->getContent(), true);
        $response = [];

        if (! $uuid) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(new Problem("Missing UUID"));
        }
        try {
            $this->dataImportHandler->updateCaseData(
                $uuid,
                'idMethod',
                'S',
                $data['idMethod']
            );
        } catch (\Exception $exception) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_500);
            return new JsonModel(new Problem($exception->getMessage()));
        }

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);
        $response['result'] = "Updated";

        return new JsonModel($response);
    }

    public function addSearchPostcodeAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        $data = json_decode($this->getRequest()->getContent(), true);
        $response = [];

        if (! $uuid) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(new Problem('Missing UUID'));
        }
        try {
            $this->dataImportHandler->updateCaseData(
                $uuid,
                'searchPostcode',
                'S',
                $data['selected_postcode']
            );
        } catch (\Exception $exception) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_500);
            return new JsonModel(new Problem($exception->getMessage()));
        }

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);
        $response['result'] = "Updated";

        return new JsonModel($response);
    }

    public function addSelectedPostofficeAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        $data = json_decode($this->getRequest()->getContent(), true);
        $response = [];

        if (! $uuid) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(new Problem('Missing UUID'));
        }
        $counterServiceMap = [
            "selectedPostOffice" => $data['selected_postoffice']
        ];

        try {
            $this->dataImportHandler->updateCaseData(
                $uuid,
                'counterService',
                'M',
                array_map(fn (mixed $v) => [
                    'S' => $v
                ], $counterServiceMap),
            );
        } catch (\Exception $exception) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_500);
            return new JsonModel(new Problem($exception->getMessage()));
        }

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);
        $response['result'] = "Updated";

        return new JsonModel($response);
    }

    public function confirmSelectedPostofficeAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        $data = json_decode($this->getRequest()->getContent(), true);
        /** @var CaseData $caseData */
        $caseData = $this->dataQueryHandler->getCaseByUUID($uuid);
        $response = [];

        if (! $uuid) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(new Problem('Missing UUID'));
        }

        $counterServiceMap = [];
        if ($caseData->counterService !== null) {
            $counterServiceMap["selectedPostOffice"] = $caseData->counterService->selectedPostOffice;
        }
        $counterServiceMap["selectedPostOfficeDeadline"] = $data['deadline'];

        try {
            $this->dataImportHandler->updateCaseData(
                $uuid,
                'counterService',
                'M',
                array_map(fn (mixed $v) => [
                    'S' => $v
                ], $counterServiceMap),
            );
        } catch (\Exception $exception) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_500);
            return new JsonModel(new Problem($exception->getMessage()));
        }

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);
        $response['result'] = "Updated";

        return new JsonModel($response);
    }

    public function addCaseLpaAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        $lpa = $this->params()->fromRoute('lpa');
        $data = $this->dataQueryHandler->getCaseByUUID($uuid);
        $response = [];
        $status = Response::STATUS_CODE_200;
        try {
            /**
             * @psalm-suppress PossiblyNullPropertyFetch
             */
            $lpas = $data->lpas;
            if (! in_array($lpa, $lpas)) {
                $lpas[] = $lpa;
                $this->dataImportHandler->updateCaseData(
                    $uuid,
                    'lpas',
                    'SS',
                    $lpas
                );
                $response['result'] = "Updated";
            } else {
                $response['result'] = "LPA is already added to this case";
            }
        } catch (\Exception $exception) {
            $status = Response::STATUS_CODE_400;
            $response['exception'] = $exception->getMessage();
        }

        $this->getResponse()->setStatusCode($status);
        return new JsonModel($response);
    }

    public function removeCaseLpaAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        $lpa = $this->params()->fromRoute('lpa');
        $data = $this->dataQueryHandler->getCaseByUUID($uuid);
        $response = [];
        $status = Response::STATUS_CODE_200;
        try {
            /**
             * @psalm-suppress PossiblyNullPropertyFetch
             */
            $lpas = $data->lpas;
            if (in_array($lpa, $lpas)) {
                $keptLpas = [];                 //this is inelegant but works, while popping the
                foreach ($lpas as $keptLpa) {   //value out of the existing array breaks the code
                    if ($keptLpa !== $lpa) {
                        $keptLpas[] = $keptLpa;
                    }
                }
                $this->dataImportHandler->updateCaseData(
                    $uuid,
                    'lpas',
                    'SS',
                    $keptLpas
                );
                $response['result'] = "Removed";
            } else {
                $response['result'] = "LPA is not added to this case";
            }
        } catch (\Exception $exception) {
            $status = Response::STATUS_CODE_400;
            $response = new Problem('Cannot remove LPA from case', extra: ['exception' => $exception->getMessage()]);
        }

        $this->getResponse()->setStatusCode($status);
        return new JsonModel($response);
    }

    public function saveAlternateAddressToCaseAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        $data = json_decode($this->getRequest()->getContent(), true);
        $response = [];
        $status = Response::STATUS_CODE_200;

        if (! $uuid) {
            $status = Response::STATUS_CODE_400;
            $this->getResponse()->setStatusCode($status);
            $response = [
                "error" => "Missing UUID"
            ];
            return new JsonModel($response);
        }

        try {
            $this->dataImportHandler->updateCaseData(
                $uuid,
                'alternateAddress',
                'M',
                array_map(fn (mixed $v) => [
                    'S' => $v
                ], $data),
            );
        } catch (\Exception $exception) {
            $response['result'] = "Not Updated";
            $response['error'] = $exception->getMessage();
            return new JsonModel($response);
        }

        $this->getResponse()->setStatusCode($status);
        $response['result'] = "Updated";

        return new JsonModel($response);
    }

    public function setDocumentCompleteAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        //$data = json_decode($this->getRequest()->getContent(), true);
        $response = [];
        $status = Response::STATUS_CODE_200;

        if (! $uuid) {
            $status = Response::STATUS_CODE_400;
            $this->getResponse()->setStatusCode($status);
            $response = [
                "error" => "Missing UUID"
            ];
            return new JsonModel($response);
        }

        try {
            $this->dataImportHandler->updateCaseData(
                $uuid,
                'documentComplete',
                'BOOL',
                true
            );
        } catch (\Exception $exception) {
            $response['result'] = "Not Updated";
            $response['error'] = $exception->getMessage();
            return new JsonModel($response);
        }

        $this->getResponse()->setStatusCode($status);
        $response['result'] = "Updated";

        return new JsonModel($response);
    }

    public function updateDobAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        $dob = $this->params()->fromRoute('dob');
        //$data = json_decode($this->getRequest()->getContent(), true);
        $response = [];
        $status = Response::STATUS_CODE_200;

        if (! $uuid) {
            $status = Response::STATUS_CODE_400;
            $this->getResponse()->setStatusCode($status);
            $response = [
                "error" => "Missing UUID"
            ];
            return new JsonModel($response);
        }

        if (! $dob) {
            $status = Response::STATUS_CODE_400;
            $this->getResponse()->setStatusCode($status);
            $response = [
                "error" => "Missing Date of Birth"
            ];
            return new JsonModel($response);
        }

        try {
            $this->dataImportHandler->updateCaseData(
                $uuid,
                'dob',
                'S',
                $dob
            );
        } catch (\Exception $exception) {
            $response['result'] = "Not Updated";
            $response['error'] = $exception->getMessage();
            return new JsonModel($response);
        }

        $this->getResponse()->setStatusCode($status);
        $response['result'] = "Updated";

        return new JsonModel($response);
    }

    public function updateCpPoIdAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        $data = json_decode($this->getRequest()->getContent(), true);
        $response = [];
        $status = Response::STATUS_CODE_200;

        if (! $uuid) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_500);
            return new JsonModel(new Problem("Missing UUID"));
        }

        try {
            $this->dataImportHandler->updateCaseData(
                $uuid,
                'idMethodIncludingNation',
                'M',
                array_map(fn (mixed $v) => [
                    'S' => $v
                ], $data),
            );
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_500);
            return new JsonModel(new Problem($exception->getMessage()));
        }

        $this->getResponse()->setStatusCode($status);
        $response['result'] = "Updated";

        return new JsonModel($response);
    }

    public function updateProgressAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        $data = json_decode(
            $this->getRequest()->getContent(),
            true
        );

        $response = [];
        $status = Response::STATUS_CODE_200;

        if (! $uuid) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_500);
            return new JsonModel(new Problem("Missing UUID"));
        }

        try {
            $this->dataImportHandler->updateCaseData(
                $uuid,
                'progressPage',
                'M',
                array_map(fn (mixed $v) => [
                    'S' => $v
                ], $data),
            );
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_500);
            return new JsonModel(new Problem($exception->getMessage()));
        }

        $this->getResponse()->setStatusCode($status);
        $response['result'] = "Progress recorded at " . $uuid . '/' . $data['route'];

        return new JsonModel($response);
    }
}
