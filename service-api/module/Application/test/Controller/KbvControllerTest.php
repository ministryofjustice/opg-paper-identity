<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Controller\KbvController;
use Application\Fixtures\DataQueryHandler;
use Application\Fixtures\DataWriteHandler;
use Application\KBV\KBVServiceInterface;
use Application\Model\Entity\CaseData;
use ApplicationTest\TestCase;
use Laminas\Http\Response;
use Laminas\Stdlib\ArrayUtils;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;

class KbvControllerTest extends TestCase
{
    private DataQueryHandler&MockObject $dataQueryHandlerMock;
    private KBVServiceInterface&MockObject $KBVServiceMock;
    private DataWriteHandler&MockObject $dataImportHandler;

    public function setUp(): void
    {
        // The module configuration should still be applicable for tests.
        // You can override configuration here with test case specific values,
        // such as sample view templates, path stacks, module_listener_options,
        // etc.
        $configOverrides = [];

        $this->setApplicationConfig(ArrayUtils::merge(
            include __DIR__ . '/../../../../config/application.config.php',
            $configOverrides
        ));

        $this->dataQueryHandlerMock = $this->createMock(DataQueryHandler::class);
        $this->KBVServiceMock = $this->createMock(KBVServiceInterface::class);
        $this->dataImportHandler = $this->createMock(DataWriteHandler::class);


        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(DataQueryHandler::class, $this->dataQueryHandlerMock);
        $serviceManager->setService(DataWriteHandler::class, $this->dataImportHandler);
        $serviceManager->setService(KBVServiceInterface::class, $this->KBVServiceMock);
    }

    /**
     * @dataProvider kbvAnswersData
     */
    public function testKbvAnswers(
        string $uuid,
        array $provided,
        CaseData $actual,
        string $result,
        int $status
    ): void {
        if ($result !== 'error') {
            $this->dataQueryHandlerMock
                ->expects($this->once())->method('getCaseByUUID')
                ->with($uuid)
                ->willReturn($actual);
        }

        $this->dispatchJSON(
            '/cases/' . $uuid . '/kbv-answers',
            'POST',
            $provided
        );
        $this->assertResponseStatusCode($status);
        $this->assertModuleName('application');
        $this->assertControllerName(KbvController::class);
        $this->assertControllerClass('KbvController');
        $this->assertMatchedRouteName('check_kbv_answers');

        if ($result === "error") {
            $response = json_decode($this->getResponse()->getContent(), true);
            $this->assertEquals('Missing UUID or unable to find case', $response['title']);
        } else {
            $this->assertEquals('{"result":"' . $result . '"}', $this->getResponse()->getContent());
        }
    }

    public static function kbvAnswersData(): array
    {
        $uuid = 'e32a4d31-f15b-43f8-9e21-2fb09c8f45e7';
        $invalidUUID = 'asdkfh3984ahksdjka';
        $provided = [
            'answers' => [
                'one' => 'VoltWave',
                'two' => 'Germanotta',
                'tree' => 'July',
                'four' => 'Pink',
            ],
        ];
        $providedIncomplete = $provided;
        unset($providedIncomplete['answers']['four']);

        $providedIncorrect = $provided;
        $providedIncorrect['answers']['two'] = 'incorrect answer';

        $actual = CaseData::fromArray([
            'personType' => 'donor',
            'firstName' => '',
            'lastName' => '',
            'dob' => '',
            'lpas' => [],
            'address' => [],
        ]);

        $actual->kbvQuestions = json_encode([
            'one' => ['answer' => 'VoltWave'],
            'two' => ['answer' => 'Germanotta'],
            'tree' => ['answer' => 'July'],
            'four' => ['answer' => 'Pink'],
        ]);

        return [
            [$uuid, $provided, $actual, 'pass', Response::STATUS_CODE_200],
            [$uuid, $providedIncomplete, $actual, 'fail', Response::STATUS_CODE_200],
            [$uuid, $providedIncorrect, $actual, 'fail', Response::STATUS_CODE_200],
            [$invalidUUID, $provided, $actual, 'error', Response::STATUS_CODE_400],
        ];
    }

    public function testKBVQuestionsWithNoUUID(): void
    {
        $this->dispatch('/cases/kbv-questions', 'GET');
        $this->assertResponseStatusCode(400);
        $this->assertModuleName('application');
        $this->assertControllerName(KbvController::class);
        $this->assertControllerClass('KbvController');
        $this->assertMatchedRouteName('get_kbv_questions');

        $response = json_decode($this->getResponse()->getContent(), true);
        $this->assertEquals('Missing UUID', $response['title']);
    }

    /**
     * @throws Exception
     */
    public function testKBVQuestionsWithVerifiedDocsCaseGeneratesQuestions(): void
    {
        $caseData = CaseData::fromArray([
            'personType' => '',
            'firstName' => 'test',
            'lastName' => 'name',
            'dob' => '',
            'lpas' => [],
            'address' => [],
        ]);

        $caseData->documentComplete = true;

        $formattedQuestions = $this->formattedQuestions();

        $this->dataQueryHandlerMock
            ->expects($this->once())->method('getCaseByUUID')
            ->with('a9bc8ab8-389c-4367-8a9b-762ab3050999')
            ->willReturn($caseData);

        $this->KBVServiceMock
            ->expects($this->once())->method('fetchFormattedQuestions')
            ->with('a9bc8ab8-389c-4367-8a9b-762ab3050999')
            ->willReturn($formattedQuestions);

        $this->dispatch('/cases/a9bc8ab8-389c-4367-8a9b-762ab3050999/kbv-questions', 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertStringContainsString('Who is your electricity supplier?', $this->getResponse()->getContent());
        $this->assertModuleName('application');
        $this->assertControllerName(KbvController::class);
        $this->assertControllerClass('KbvController');
        $this->assertMatchedRouteName('get_kbv_questions');
    }

    /**
     * @throws Exception
     */
    public function testKBVQuestionsWithVerifiedDocsCaseAndExistingQuestions(): void
    {
        $caseData = CaseData::fromArray([
            'personType' => '',
            'firstName' => 'test',
            'lastName' => 'name',
            'dob' => '',
            'lpas' => [],
            'address' => [],
        ]);

        $caseData->kbvQuestions = json_encode($this->formattedQuestions());
        $caseData->documentComplete = true;

        $this->dataQueryHandlerMock
            ->expects($this->once())->method('getCaseByUUID')
            ->with('a9bc8ab8-389c-4367-8a9b-762ab3050999')
            ->willReturn($caseData);

        $this->KBVServiceMock
            ->expects($this->never())->method('fetchFormattedQuestions')
            ->with('a9bc8ab8-389c-4367-8a9b-762ab3050999');

        $this->dispatch('/cases/a9bc8ab8-389c-4367-8a9b-762ab3050999/kbv-questions', 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertStringContainsString('Who is your electricity supplier?', $this->getResponse()->getContent());
        $this->assertModuleName('application');
        $this->assertControllerName(KbvController::class);
        $this->assertControllerClass('KbvController');
        $this->assertMatchedRouteName('get_kbv_questions');
    }

    /**
     * @throws Exception
     */
    public function testKBVQuestionsWithUnVerifiedDocsCase(): void
    {
        $response = '{"error":"Document checks incomplete or unable to locate case"}';
        $caseData = CaseData::fromArray([
            'personType' => '',
            'firstName' => 'test',
            'lastName' => 'name',
            'dob' => '',
            'lpas' => [],
            'address' => [],
        ]);

        $this->dataQueryHandlerMock->expects($this->once())->method('getCaseByUUID')
            ->with('a9bc8ab8-389c-4367-8a9b-762ab3050999')
            ->willReturn($caseData);

        $this->dispatch('/cases/a9bc8ab8-389c-4367-8a9b-762ab3050999/kbv-questions', 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertEquals($response, $this->getResponse()->getContent());
        $this->assertModuleName('application');
        $this->assertControllerName(KbvController::class);
        $this->assertControllerClass('KbvController');
        $this->assertMatchedRouteName('get_kbv_questions');
    }

    public function formattedQuestions(): array
    {
        return [
            'formattedQuestions' => [
                'one' => [
                    'question' => 'Who is your electricity supplier?',
                    'prompts' => [
                        0 => 'VoltWave',
                        1 => 'Glow Electric',
                        2 => 'Powergrid Utilities',
                        3 => 'Bright Bristol Power',
                    ],
                    'answer' => 'VoltWave',
                ],
                'two' => [
                    'question' => 'How much was your last phone bill?',
                    'prompts' => [
                        0 => "£5.99",
                        1 => "£11",
                        2 => "£16.84",
                        3 => "£1.25",
                    ],
                    'answer' => "£5.99",
                ],
            ],
            'questionsWithoutAnswers' => [
                'one' => [
                    'question' => 'Who is your electricity supplier?',
                    'prompts' => [
                        0 => 'VoltWave',
                        1 => 'Glow Electric',
                        2 => 'Powergrid Utilities',
                        3 => 'Bright Bristol Power',
                    ],
                ],
                'two' => [
                    'question' => 'How much was your last phone bill?',
                    'prompts' => [
                        0 => "£5.99",
                        1 => "£11",
                        2 => "£16.84",
                        3 => "£1.25",
                    ],
                ],
            ],
        ];
    }
}