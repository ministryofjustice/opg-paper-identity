<?php

declare(strict_types=1);

namespace ApplicationTest\Unit\Services;

use Application\Auth\JwtGenerator;
use Application\Exceptions\PostcodeInvalidException;
use Application\Exceptions\UidInvalidException;
use Application\Services\SiriusApiService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Laminas\Http\Headers;
use Laminas\Http\Request;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @psalm-suppress DeprecatedMethod
 * False positive with `GuzzleHttp\Client::__call` being deprecated
 * Supression can be removed when Guzzle 8 is released as it removes the method
 */
class SiriusApiServiceTest extends TestCase
{
    public function testCheckAuthSuccess(): void
    {
        $clientMock = $this->createMock(Client::class);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $jwtGeneratorMock = $this->createMock(JwtGenerator::class);

        $sut = new SiriusApiService($clientMock, $loggerMock, $jwtGeneratorMock);

        $request = new Request();
        $headers = $request->getHeaders();
        assert($headers instanceof Headers);
        $headers->addHeaderLine("cookie", "mycookie=1; XSRF-TOKEN=abcd");

        $clientMock
            ->expects($this->once())
            ->method("get")
            ->with("/api/v1/users/current", [
                'headers' => [
                    'Cookie' => 'mycookie=1; XSRF-TOKEN=abcd',
                    'X-XSRF-TOKEN' => 'abcd',
                ],
            ])
            ->willReturn(new Response(200, [], '{"email":"myemail@opg.example"}'));

        $jwtGeneratorMock
            ->expects($this->once())
            ->method('setSub')
            ->with('myemail@opg.example');

        $ret = $sut->checkAuth($request);
        $this->assertTrue($ret);
    }

    public function testCheckAuthFailureNotAuthed(): void
    {
        $clientMock = $this->createMock(Client::class);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $jwtGeneratorMock = $this->createMock(JwtGenerator::class);

        $sut = new SiriusApiService($clientMock, $loggerMock, $jwtGeneratorMock);


        $request = new Request();
        $headers = $request->getHeaders();
        assert($headers instanceof Headers);
        $headers->addHeaderLine("cookie", "mycookie=1; XSRF-TOKEN=abcd");

        $exception = $this->createMock(GuzzleException::class);

        $clientMock
            ->expects($this->once())
            ->method("get")
            ->with("/api/v1/users/current", [
                'headers' => [
                    'Cookie' => 'mycookie=1; XSRF-TOKEN=abcd',
                    'X-XSRF-TOKEN' => 'abcd',
                ],
            ])
            ->willThrowException($exception);

        $ret = $sut->checkAuth($request);
        $this->assertFalse($ret);
    }

    public function testSearchAddressesByPostcodeSuccess(): void
    {
        $clientMock = $this->createMock(Client::class);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $jwtGeneratorMock = $this->createMock(JwtGenerator::class);

        $sut = new SiriusApiService($clientMock, $loggerMock, $jwtGeneratorMock);


        $request = new Request();
        $headers = $request->getHeaders();
        assert($headers instanceof Headers);
        $headers->addHeaderLine("cookie", "mycookie=1; XSRF-TOKEN=abcd");

        $responseBody = json_encode([
            [
                'addressLine1' => '10 Downing Street',
                'addressLine2' => '',
                'addressLine3' => '',
                'town' => 'London',
                'postcode' => 'SW1A 2AA',
            ],
        ], JSON_THROW_ON_ERROR);

        $clientMock
            ->expects($this->once())
            ->method("get")
            ->with('/api/v1/postcode-lookup?postcode=SW1A2AA', [
                'headers' => [
                    'Cookie' => 'mycookie=1; XSRF-TOKEN=abcd',
                    'X-XSRF-TOKEN' => 'abcd',
                ],
            ])
            ->willReturn(new Response(200, [], $responseBody));

        $result = $sut->searchAddressesByPostcode('SW1A2AA', $request);

        $this->assertCount(1, $result);
        $this->assertEquals('10 Downing Street', $result[0]['addressLine1']);
    }

    public function testSearchAddressesByPostcodeThrowsPostcodeInvalidException(): void
    {
        $clientMock = $this->createMock(Client::class);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $jwtGeneratorMock = $this->createMock(JwtGenerator::class);

        $sut = new SiriusApiService($clientMock, $loggerMock, $jwtGeneratorMock);


        $request = new Request();
        $headers = $request->getHeaders();
        assert($headers instanceof Headers);
        $headers->addHeaderLine("cookie", "mycookie=1; XSRF-TOKEN=abcd");

        $exception = $this->createMock(ClientException::class);
        $exception
            ->method('getResponse')
            ->willReturn(new Response(400));

        $clientMock
            ->expects($this->once())
            ->method("get")
            ->with('/api/v1/postcode-lookup?postcode=INVALID', [
                'headers' => [
                    'Cookie' => 'mycookie=1; XSRF-TOKEN=abcd',
                    'X-XSRF-TOKEN' => 'abcd',
                ],
            ])
            ->willThrowException($exception);

        $this->expectException(PostcodeInvalidException::class);
        $sut->searchAddressesByPostcode('INVALID', $request);
    }

    public function testSearchAddressesByPostcodeThrowsGenericException(): void
    {
        $clientMock = $this->createMock(Client::class);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $jwtGeneratorMock = $this->createMock(JwtGenerator::class);

        $sut = new SiriusApiService($clientMock, $loggerMock, $jwtGeneratorMock);


        $request = new Request();
        $headers = $request->getHeaders();
        assert($headers instanceof Headers);
        $headers->addHeaderLine("cookie", "mycookie=1; XSRF-TOKEN=abcd");

        $exception = $this->createMock(GuzzleException::class);

        $clientMock
            ->expects($this->once())
            ->method("get")
            ->with('/api/v1/postcode-lookup?postcode=SW1A2AA', [
                'headers' => [
                    'Cookie' => 'mycookie=1; XSRF-TOKEN=abcd',
                    'X-XSRF-TOKEN' => 'abcd',
                ],
            ])
            ->willThrowException($exception);

        $this->expectException(GuzzleException::class);
        $sut->searchAddressesByPostcode('SW1A2AA', $request);
    }

    public function testGetLpaByUid(): void
    {
        $uId = 'M-0000-0000-0000';
        $lpa = ['lpa'];

        $clientMock = $this->createMock(Client::class);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $jwtGeneratorMock = $this->createMock(JwtGenerator::class);

        $sut = new SiriusApiService($clientMock, $loggerMock, $jwtGeneratorMock);

        $request = new Request();
        $headers = $request->getHeaders();
        assert($headers instanceof Headers);
        $headers->addHeaderLine("cookie", "mycookie=1; XSRF-TOKEN=abcd");

        $clientMock
            ->expects($this->once())
            ->method("get")
            ->with("/api/v1/digital-lpas/$uId", [
                'headers' => [
                    'Cookie' => 'mycookie=1; XSRF-TOKEN=abcd',
                    'X-XSRF-TOKEN' => 'abcd',
                ],
            ])
            ->willReturn(new Response(200, [], json_encode($lpa, JSON_THROW_ON_ERROR)));

        $result = $sut->getLpaByUid($uId, $request);

        $this->assertEquals($lpa, $result);
    }

    public function testGetLpaByUidThrowsValidationException(): void
    {
        $uId = 'invalid-uid';

        $clientMock = $this->createMock(Client::class);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $jwtGeneratorMock = $this->createMock(JwtGenerator::class);

        $sut = new SiriusApiService($clientMock, $loggerMock, $jwtGeneratorMock);

        $request = new Request();

        $this->expectException(UidInvalidException::class);
        $this->expectExceptionMessage('The LPA needs to be valid in the format M-XXXX-XXXX-XXXX');
        $sut->getLpaByUid($uId, $request);
    }

    public function testGetAllLinkedLpasByUid(): void
    {
        $lpas = [
            'M-0000-0000-0000' => [
                'opg.poas.sirius' => [
                    'linkedDigitalLpas' => [
                        ['uId' => 'M-0000-0000-0001'],
                        ['uId' => 'M-0000-0000-0002'],
                    ],
                ],
            ],
            'M-0000-0000-0001' => ['lpa1'],
            'M-0000-0000-0002' => ['lpa2'],
        ];

        $clientMock = $this->createMock(Client::class);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $jwtGeneratorMock = $this->createMock(JwtGenerator::class);

        $sut = new SiriusApiService($clientMock, $loggerMock, $jwtGeneratorMock);

        $request = new Request();
        $headers = $request->getHeaders();
        assert($headers instanceof Headers);
        $headers->addHeaderLine("cookie", "mycookie=1; XSRF-TOKEN=abcd");

        $expectedHeader = [
            'headers' => [
                'Cookie' => 'mycookie=1; XSRF-TOKEN=abcd',
                'X-XSRF-TOKEN' => 'abcd',
            ],
        ];

        $clientMock
            ->expects($this->exactly(3))
            ->method("get")
            ->willReturnCallback(fn (string $url, array $header) => match (true) {
                $url === '/api/v1/digital-lpas/M-0000-0000-0000' && $header === $expectedHeader =>
                    new Response(200, [], json_encode($lpas['M-0000-0000-0000'], JSON_THROW_ON_ERROR)),
                $url === '/api/v1/digital-lpas/M-0000-0000-0001' && $header === $expectedHeader =>
                    new Response(200, [], json_encode($lpas['M-0000-0000-0001'], JSON_THROW_ON_ERROR)),
                $url === '/api/v1/digital-lpas/M-0000-0000-0002' && $header === $expectedHeader =>
                    new Response(200, [], json_encode($lpas['M-0000-0000-0002'], JSON_THROW_ON_ERROR)),
                default => self::fail('Did not expect:' . print_r($url, true))
            });

        $result = $sut->getAllLinkedLpasByUid('M-0000-0000-0000', $request);
        $this->assertEquals($lpas, $result);
    }

    public function testAddNoteSuccess(): void
    {
        $clientMock = $this->createMock(Client::class);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $jwtGeneratorMock = $this->createMock(JwtGenerator::class);
        $sut = new SiriusApiService($clientMock, $loggerMock, $jwtGeneratorMock);

        $request = new Request();
        $uid = 'M-0000-0000-0000';
        $name = 'ID Check Abandoned';
        $type = 'ID Check Incomplete';
        $description = 'Reason: Call dropped\n\nCustom notes';

        $lpaDetails = [
            'opg.poas.sirius' => [
                'id' => 1234,
                'donor' => ['id' => 5678],
            ],
        ];

        /** @psalm-suppress PossiblyFalseArgument */
        $clientMock->expects($this->once())
            ->method('get')
            ->with('/api/v1/digital-lpas/' . $uid, ['headers' => []])
            ->willReturn(new Response(200, [], json_encode($lpaDetails)));

        $clientMock->expects($this->once())
            ->method('post')
            ->with(
                '/api/v1/persons/5678/notes',
                [
                    'headers' => null,
                    'json' => [
                        'ownerId' => 1234,
                        'ownerType' => 'case',
                        'name' => $name,
                        'type' => $type,
                        'description' => $description,
                    ],
                ]
            )
            ->willReturn(new Response(201));

        $sut->addNote($request, $uid, $name, $type, $description);
    }
}
