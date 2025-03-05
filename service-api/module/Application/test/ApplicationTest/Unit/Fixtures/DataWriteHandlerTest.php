<?php

declare(strict_types=1);

namespace ApplicationTest\ApplicationTest\Unit\Fixtures;

use Application\Fixtures\DataWriteHandler;
use Application\Model\Entity\CaseData;
use Application\Model\IdMethod;
use Aws\CommandInterface;
use Aws\DynamoDb\DynamoDbClient;
use Aws\Exception\AwsException;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DataWriteHandlerTest extends TestCase
{
    private DynamoDbClient|MockObject $dynamoDbClientMock;
    private LoggerInterface|MockObject $loggerMock;
    private DataWriteHandler $sut;

    public function setUp(): void
    {
        parent::setUp();

        // Mocking the DynamoDB client and logger
        $this->dynamoDbClientMock = $this->createMock(DynamoDbClient::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        // Create an instance of SUT with mocked dependencies
        $this->sut = new DataWriteHandler($this->dynamoDbClientMock, 'cases', $this->loggerMock);
    }

    /**
     * @throws Exception
     * @psalm-suppress UndefinedMagicMethod
     * @psalm-suppress PossiblyUndefinedMethod
     */
    public function testInsertData(): void
    {
        $case = CaseData::fromArray([
            'id' => '2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc',
            'personType' => 'donor',
            'claimedIdentity' => [
                'firstName' => 'Maria',
                'lastName' => 'Neldon',
                'dob' => '1980-01-01',
                'address' => [
                    '1 Street',
                    'Town',
                    'Postcode'
                ]
            ],
            'lpas' => []
        ]);

        // Stubbing the putItem method of DynamoDB client
        $this->dynamoDbClientMock->expects($this->once())
            ->method('__call')
            ->with(
                'putItem',
                $this->callback(function ($params) {
                    // Ensure correct parameters passed to putItem
                    $input = $params[0];
                    $this->assertEquals('cases', $input['TableName']);
                    $this->assertArrayHasKey('Item', $input);
                    $this->assertEquals(['S' => 'Maria'], $input['Item']['claimedIdentity']['M']['firstName']);
                    $this->assertEquals(['S' => 'Neldon'], $input['Item']['claimedIdentity']['M']['lastName']);
                    return true;
                })
            );

        // Expect the logger to be called if an exception occurs
        $this->loggerMock->expects($this->never())->method('error');

        // Call the insertData method with test data
        $this->sut->insertUpdateData($case);
    }


    /**
     * @throws Exception
     * @psalm-suppress UndefinedMagicMethod
     * @psalm-suppress PossiblyUndefinedMethod
     */
    public function testInsertDataWithException(): void
    {
        $caseData = CaseData::fromArray([
            'id' => '2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc',
            'claimedIdentity' => [
                'firstName' => 'Maria',
                'lastName' => 'Neldon',
                'dob' => '1980-01-01',
                'address' => [
                    '1 Street',
                    'Town',
                    'Postcode'
                ]
            ],
            'personType' => 'donor',
            'lpas' => []
        ]);

        $commandMock = $this->createMock(CommandInterface::class);

        // Stubbing the putItem method of DynamoDB client to throw an exception
        $this->dynamoDbClientMock->expects($this->once())
            ->method('__call')
            ->with('putItem')
            ->willThrowException(new AwsException('Test exception', $commandMock));

        // Expect the logger to be called with error message and data
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains(
                    'Unable to save data [Test exception] to cases'
                ),
                $this->callback(function ($context) use ($caseData) {
                    // Ensure the data passed to logger contains the correct item
                    $this->assertArrayHasKey('data', $context);
                    $this->assertEquals($caseData, $context['data']);
                    return true;
                })
            );

        // Call the insertData method with test data
        $this->sut->insertUpdateData($caseData);
    }

    /**
     * @throws Exception
     * @psalm-suppress UndefinedMagicMethod
     * @psalm-suppress PossiblyUndefinedMethod
     */
    public function testUpdateCaseData(): void
    {
        // Stubbing the putItem method of DynamoDB client
        $this->dynamoDbClientMock->expects($this->once())
            ->method('__call')
            ->with(
                'updateItem',
                $this->callback(function ($params) {
                    // Ensure correct parameters passed to updateItem
                    $input = $params[0];
                    $this->assertEquals(['id' => ['S' => 'a9bc8ab8-389c-4367-8a9b-762ab3050491']], $input['Key']);
                    $this->assertArrayHasKey('UpdateExpression', $input);
                    $this->assertEquals(
                        ['#AT0' => 'identityIQ', '#AT1' => 'kbvQuestions'],
                        $input['ExpressionAttributeNames']
                    );

                    return true;
                })
            );

        // Expect the logger to be called if an exception occurs
        $this->loggerMock->expects($this->never())->method('error');

        // Call the updateCaseData method with test data
        $this->sut->updateCaseData(
            'a9bc8ab8-389c-4367-8a9b-762ab3050491',
            'identityIQ.kbvQuestions',
            [
                'one' => [
                    'question' => 'Who is your electricity provider?',
                    'prompts' => [
                        0 => 'VoltWave',
                        1 => 'Glow Electric',
                        2 => 'Powergrid Utilities',
                        3 => 'Bright Bristol Power'
                    ],
                    'answered' => false,
                ],
            ]
        );
    }
    /**
     * @psalm-suppress UndefinedMagicMethod
     * @psalm-suppress PossiblyUndefinedMethod
     */
    public function testUpdateCaseDataChildAttribute(): void
    {
        $this->dynamoDbClientMock->expects($this->once())
            ->method('__call')
            ->with(
                'updateItem',
                $this->callback(function ($params) {
                    // Ensure correct parameters passed to updateItem
                    $input = $params[0];
                    $this->assertEquals(['id' => ['S' => 'a9bc8ab8-389c-4367-8a9b-762ab3050491']], $input['Key']);
                    $this->assertArrayHasKey('UpdateExpression', $input);
                    $this->assertEquals(
                        ['#AT0' => 'counterService','#AT1' => 'notificationState'],
                        $input['ExpressionAttributeNames']
                    );
                    return true;
                })
            );

        // Expect the logger to be called if an exception occurs
        $this->loggerMock->expects($this->never())->method('error');

        // Call the updateCaseData method with test data
        $this->sut->updateCaseData(
            'a9bc8ab8-389c-4367-8a9b-762ab3050491',
            'counterService.notificationState',
            'complete'
        );
    }

    /**
     * @throws Exception
     * @psalm-suppress UndefinedMagicMethod
     * @psalm-suppress PossiblyUndefinedMethod
     */
    public function testUpdateCaseIdMethod(): void
    {
        $this->dynamoDbClientMock->expects($this->once())
            ->method('__call')
            ->with(
                'updateItem',
                $this->callback(function ($params) {
                    // Ensure correct parameters passed to updateItem
                    $input = $params[0];
                    $this->assertEquals(['id' => ['S' => 'a9bc8ab8-389c-4367-8a9b-762ab3050491']], $input['Key']);
                    $this->assertArrayHasKey('UpdateExpression', $input);
                    $this->assertEquals(['#AT0' => 'idMethodIncludingNation'], $input['ExpressionAttributeNames']);

                    return true;
                })
            );

        // Expect the logger to be called if an exception occurs
        $this->loggerMock->expects($this->never())->method('error');

        // Call the updateCaseData method with test data
        $this->sut->updateCaseData(
            'a9bc8ab8-389c-4367-8a9b-762ab3050491',
            'idMethodIncludingNation',
            IdMethod::PassportNumber->value
        );
    }

    /**
     * @throws Exception
     * @psalm-suppress UndefinedMagicMethod
     * @psalm-suppress PossiblyUndefinedMethod
     */
    public function testUpdateCaseDataWithBadData(): void
    {
        $this->dynamoDbClientMock->expects($this->never())
            ->method('__call');

        // Expect the logger to be called if an exception occurs
        $this->loggerMock->expects($this->never())->method('error');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"an invalid value" is not a valid value for documentComplete');

        // Call the updateCaseData method with test data
        $this->sut->updateCaseData(
            'a9bc8ab8-389c-4367-8a9b-762ab3050491',
            'documentComplete',
            'an invalid value'
        );
    }
}
