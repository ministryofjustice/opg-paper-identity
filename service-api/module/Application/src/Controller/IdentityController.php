<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Nino\ValidatorInterface;
use Application\DrivingLicense\ValidatorInterface as LicenseValidatorInterface;
use Application\Passport\ValidatorInterface as PassportValidator;
use Application\KBV\KBVServiceInterface;
use Application\Fixtures\DataImportHandler;
use Application\Fixtures\DataQueryHandler;
use Application\Model\Entity\CaseData;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
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
        private readonly KBVServiceInterface $KBVService
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

        if ($caseData->isValid()) {
            $uuid = Uuid::uuid4();
            $item = [
                'id'            => ['S' => $uuid->toString()],
                'personType'     => ['S' => $data["personType"]],
                'firstName'     => ['S' => $data["firstName"]],
                'lastName'      => ['S' => $data["lastName"]],
                'dob'           => ['S' => $data["dob"]],
                'lpas'          => ['SS' => $data['lpas']]
            ];

            $this->dataImportHandler->insertData('cases', $item);

            $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);
            return new JsonModel(['uuid' => $uuid]);
        }

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);

        return new JsonModel(['error' => 'Invalid data']);
    }

    public function detailsAction(): JsonModel
    {
        /** @var string $uuid */
        $uuid = $this->getRequest()->getQuery('uuid');

        if (! $uuid) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel(['error' => 'Missing uuid']);
        }

        $data = $this->dataQueryHandler->getCaseByUUID($uuid);

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);

        if (! empty($data)) {
            return new JsonModel($data[0]);
        }

        return new JsonModel(['error' => 'Invalid uuid']);
    }

    public function testdataAction(): JsonModel
    {
        $this->dataImportHandler->load();
        $data = $this->dataQueryHandler->returnAll('cases');

        /**
         * @psalm-suppress InvalidArgument
         * @see https://github.com/laminas/laminas-view/issues/239
         */
        return new JsonModel($data);
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
            $response = [
                "error" => "Missing UUID"
            ];
            return new JsonModel($response);
        }

        $case = $this->dataQueryHandler->getCaseByUUID($uuid);

        if (! $case || $case[0]['documentComplete'] === false) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);
            $response = [
                "error" => "Document checks incomplete or unable to locate case"
            ];
            return new JsonModel($response);
        }

        $questionsWithoutAnswers = [];

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);

        if (array_key_exists('kbvQuestions', $case[0])) {
            $questions = json_decode($case[0]['kbvQuestions'], true);

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

        if (! $uuid || ! $case) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            $response = [
                "error" => "Missing UUID or unable to find case"
            ];
            return new JsonModel($response);
        }

        $questions = json_decode($case[0]['kbvQuestions'], true);
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
}
