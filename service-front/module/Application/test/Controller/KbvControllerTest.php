<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\DonorFlowController;
use Application\Controller\IndexController;
use Application\Controller\KbvController;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class KbvControllerTest extends AbstractHttpControllerTestCase
{
    private OpgApiServiceInterface&MockObject $opgApiServiceMock;

    public function setUp(): void
    {
        $this->setApplicationConfig(include __DIR__ . '/../../../../config/application.config.php');

        $this->opgApiServiceMock = $this->createMock(OpgApiServiceInterface::class);

        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(OpgApiServiceInterface::class, $this->opgApiServiceMock);
    }

    public function testKbvQuestionsFormRenders(): void
    {
        $mockResponseData = [];
        $mockUuid = 'uuid';

        $mockResponseData[$mockUuid] = [
            "one" => [
                "question" => "Who provides your mortgage?",
                "number" => "one",
                "prompts" => [
                    0 => "Nationwide",
                    1 => "Halifax",
                    2 => "Lloyds",
                    3 => "HSBC",
                ]
            ],
            "two" => [
                "question" => "Who provides your personal mobile contract?",
                "number" => "two",
                "prompts" => [
                    0 => "EE",
                    1 => "Vodafone",
                    2 => "BT",
                    3 => "iMobile",
                ]
            ],
            "three" => [
                "question" => "What are the first two letters of the last name of another
                person on the electoral register at your address?",
                "number" => "three",
                "prompts" => [
                    0 => "Ka",
                    1 => "Ch",
                    2 => "Jo",
                    3 => "None of the above",
                ]
            ],
            "four" => [
                "question" => "Who provides your current account?",
                "number" => "four",
                "prompts" => [
                    0 => "Santander",
                    1 => "HSBC",
                    2 => "Halifax",
                    3 => "Nationwide",
                ]
            ]
        ];

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getIdCheckQuestions')
            ->willReturn($mockResponseData[$mockUuid]);

        $this->dispatch('/' . $mockUuid . '/id-verify-questions', 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(KbvController::class); // as specified in router's controller name alias
        $this->assertControllerClass('KbvController');
        $this->assertMatchedRouteName('root/id_verify_questions');
    }
}
