<?php

declare(strict_types=1);

namespace ApplicationTest\Feature\Controller;

use Application\Controller\KbvController;
use Application\Enums\DocumentType;
use Application\Fixtures\DataQueryHandler;
use Application\Helpers\CaseOutcomeCalculator;
use Application\KBV\AnswersOutcome;
use Application\KBV\KBVServiceInterface;
use Application\Model\Entity\CaseData;
use Application\Model\Entity\CaseProgress;
use Application\Model\Entity\Kbvs;
use ApplicationTest\TestCase;
use DateTimeImmutable;
use Laminas\Http\Response;
use Laminas\Stdlib\ArrayUtils;
use Lcobucci\Clock\FrozenClock;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Clock\ClockInterface;

class KbvControllerTest extends TestCase
{
    private DataQueryHandler&MockObject $dataQueryHandlerMock;
    private KBVServiceInterface&MockObject $kbvServiceMock;
    private CaseOutcomeCalculator&MockObject $caseOutcomeCalculatorMock;
    private ClockInterface $mockClock;

    public function setUp(): void
    {
        // The module configuration should still be applicable for tests.
        // You can override configuration here with test case specific values,
        // such as sample view templates, path stacks, module_listener_options,
        // etc.
        $configOverrides = [];

        $this->setApplicationConfig(ArrayUtils::merge(
            include __DIR__ . '/../../../../../../config/application.config.php',
            $configOverrides
        ));

        $this->dataQueryHandlerMock = $this->createMock(DataQueryHandler::class);
        $this->kbvServiceMock = $this->createMock(KBVServiceInterface::class);
        $this->caseOutcomeCalculatorMock = $this->createMock(CaseOutcomeCalculator::class);
        $this->mockClock = new FrozenClock(new DateTimeImmutable('2024-11-06 13:49:30'));

        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(DataQueryHandler::class, $this->dataQueryHandlerMock);
        $serviceManager->setService(KBVServiceInterface::class, $this->kbvServiceMock);
        $serviceManager->setService(CaseOutcomeCalculator::class, $this->caseOutcomeCalculatorMock);
        $serviceManager->setService(ClockInterface::class, $this->mockClock);
    }

    /**
     * @dataProvider kbvAnswersData
     */
    public function testKbvAnswers(
        string $uuid,
        array $provided,
        ?CaseData $caseData,
        ?AnswersOutcome $result,
        int $status
    ): void {
        if ($caseData !== null) {
            $this->dataQueryHandlerMock
                ->expects($this->once())->method('getCaseByUUID')
                ->with($uuid)
                ->willReturn($caseData);
        }

        if ($result !== null) {
            $this->kbvServiceMock->expects($this->once())
                ->method('checkAnswers')
                ->with($provided['answers'], $uuid)
                ->willReturn($result);

            if ($result->isComplete() && $caseData !== null) {
                assert(! is_null($caseData->caseProgress));
                if ($result->isPass()) {
                    $caseData->identityCheckPassed = true;
                    $caseData->caseProgress->kbvs = Kbvs::fromArray(['result' => true]);
                } else {
                    $caseData->identityCheckPassed = false;
                    $caseData->caseProgress->kbvs = Kbvs::fromArray(['result' => false]);
                }
                $this->caseOutcomeCalculatorMock->expects($this->once())
                    ->method('updateSendIdentityCheck')
                    ->with($caseData);
            }
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

        $response = json_decode($this->getResponse()->getContent(), true);

        if ($caseData === null) {
            $this->assertEquals('Missing UUID or unable to find case', $response['title']);
        } else {
            $this->assertEquals($result?->isComplete(), $response['complete']);
            $this->assertEquals($result?->isPass(), $response['passed']);
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
            'id' => $uuid,
            'personType' => 'donor',
            'claimedIdentity' => [
                'firstName' => '',
                'lastName' => '',
                'dob' => '',
                'address' => []
            ],
            'lpas' => [],
            'caseProgress' => [],
        ]);

        return [
            [$uuid, $provided, $actual, AnswersOutcome::CompletePass, Response::STATUS_CODE_200],
            [$uuid, $providedIncomplete, $actual, AnswersOutcome::Incomplete, Response::STATUS_CODE_200],
            [$uuid, $providedIncorrect, $actual, AnswersOutcome::CompleteFail, Response::STATUS_CODE_200],
            [$invalidUUID, $provided, null, null, Response::STATUS_CODE_400],
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
            'claimedIdentity' => [
                'firstName' => 'test',
                'lastName' => 'name',
                'address' => [],
                'dob' => ''
            ],
            'lpas' => [],
        ]);

        $caseData->caseProgress = CaseProgress::fromArray([
            'docCheck' => [
                'idDocument' => DocumentType::NationalInsuranceNumber->value,
                'state' => true
            ]
        ]);

        $formattedQuestions = $this->formattedQuestions();

        $this->dataQueryHandlerMock
            ->expects($this->once())->method('getCaseByUUID')
            ->with('a9bc8ab8-389c-4367-8a9b-762ab3050999')
            ->willReturn($caseData);

        $this->kbvServiceMock
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
    public function testKBVQuestionsWithUnVerifiedDocsCase(): void
    {
        $response = '{"error":"Document checks incomplete or unable to locate case"}';
        $caseData = CaseData::fromArray([
            'personType' => '',
            'claimedIdentity' => [
                'firstName' => 'test',
                'lastName' => 'name',
                'dob' => '',
                'address' => [],
            ],
            'lpas' => [],
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
                    'answered' => false,
                ],
                'two' => [
                    'question' => 'How much was your last phone bill?',
                    'prompts' => [
                        0 => "£5.99",
                        1 => "£11",
                        2 => "£16.84",
                        3 => "£1.25",
                    ],
                    'answered' => false,
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
