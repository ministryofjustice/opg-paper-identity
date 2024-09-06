<?php

declare(strict_types=1);

namespace ApplicationTest\Experian\IIQ;

use Application\Experian\IIQ\AuthManager;
use Application\Experian\IIQ\IIQService;
use Application\Experian\IIQ\Soap\IIQClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SoapHeader;

class IIQServiceTest extends TestCase
{
    public function testStartAuthenticationAttempt(): void
    {
        $questions = [
            ['id' => 1],
            ['id' => 2],
        ];

        $client = $this->createMock(IIQClient::class);

        $client->expects($this->once())
            ->method('__call')
            ->with(
                'SAA',
                $this->callback(fn ($args) => $args[0]['sAARequest']['Applicant']['Name']['Forename'] === 'Albert'),
            )
            ->willReturn((object)[
                'SAAResult' => (object)[
                    'Questions' => (object)[
                        'Question' => $questions,
                    ],
                ],
            ]);

        $sut = new IIQService(
            $this->getMockAuthManager(),
            $client,
        );

        $this->assertEquals($questions, $sut->startAuthenticationAttempt());
    }

    private function getMockAuthManager(): AuthManager&MockObject
    {
        $authManager = $this->createMock(AuthManager::class);

        $authManager->expects($this->once())
            ->method('buildSecurityHeader')
            ->willReturn(new SoapHeader('placeholder', 'header'));

        return $authManager;
    }
}
