<?php

declare(strict_types=1);

namespace ApplicationTest\Fixtures;

use Application\Fixtures\DataImportHandler;
use Aws\CommandInterface;
use Aws\DynamoDb\DynamoDbClient;
use Aws\Exception\AwsException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DataImportHandlerTest extends TestCase
{
    private DynamoDbClient | MockObject $dynamoDbClientMock;
    private LoggerInterface | MockObject $loggerMock;
    private DataImportHandler $sut;

    public function setUp(): void
    {
        parent::setUp();

        // Mocking the DynamoDB client and logger
        $this->dynamoDbClientMock = $this->createMock(DynamoDbClient::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        // Create an instance of SUT with mocked dependencies
        $this->sut = new DataImportHandler($this->dynamoDbClientMock, 'cases', $this->loggerMock);
    }

    /**
     * @throws Exception
     * @psalm-suppress UndefinedMagicMethod
     * @psalm-suppress PossiblyUndefinedMethod
     */
    public function testInsertData(): void
    {
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
                    $this->assertEquals(['id' => '123', 'name' => 'John'], $input['Item']);
                    return true;
                })
            );

        // Expect the logger to be called if an exception occurs
        $this->loggerMock->expects($this->never())->method('error');

        // Call the insertData method with test data
        $this->sut->insertData('cases', ['id' => '123', 'name' => 'John']);
    }


    /**
     * @throws Exception
     * @psalm-suppress UndefinedMagicMethod
     * @psalm-suppress PossiblyUndefinedMethod
     */
    public function testInsertDataWithException(): void
    {
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
                    'Unable to save data [Test exception] to test_table'
                ),
                $this->callback(function ($context) {
                    // Ensure the data passed to logger contains the correct item
                    $this->assertArrayHasKey('data', $context);
                    $this->assertEquals(['id' => '123', 'name' => 'John'], $context['data']);
                    return true;
                })
            );

        // Call the insertData method with test data
        $this->sut->insertData('test_table', ['id' => '123', 'name' => 'John']);
    }
}
