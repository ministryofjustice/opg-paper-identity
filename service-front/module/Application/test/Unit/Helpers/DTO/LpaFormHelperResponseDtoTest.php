<?php

declare(strict_types=1);

namespace ApplicationTest\Unit\Helpers\DTO;

use Application\Helpers\DTO\LpaFormHelperResponseDto;
use Laminas\Form\Annotation\AttributeBuilder;
use Laminas\Form\FormInterface;
use PHPUnit\Framework\TestCase;

class LpaFormHelperResponseDtoTest extends TestCase
{
    private LpaFormHelperResponseDto $lpaFormHelperResponseDto;

    private string $uuid;

    private FormInterface $form;

    private array $variables;

    private array $data;

    private array $additionalData;

    private string $status;

    private array $messages;

    public function setUp(): void
    {
        parent::setUp();

        $this->form = (new AttributeBuilder())->createForm(TestValidator::class);
        $this->uuid = "9130a21e-6e5e-4a30-8b27-76d21b747e60";

        $this->status = 'success';
        $this->messages = [];
        $this->data = [];
        $this->additionalData = [];
        $this->variables = [
            'status' => 'success',
            'messages' => [],
            'data' => [],
            'additionalData' => [],
        ];

        $this->lpaFormHelperResponseDto = new LpaFormHelperResponseDto(
            $this->uuid,
            $this->form,
            $this->status,
            $this->messages,
            $this->data,
            $this->additionalData,
        );
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(
            $this->uuid,
            $this->lpaFormHelperResponseDto->getUuid()
        );
    }

    public function testGetForm(): void
    {
        $this->assertEquals(
            $this->form,
            $this->lpaFormHelperResponseDto->getForm()
        );
    }


    public function testGetStatus(): void
    {
        $this->assertEquals(
            $this->status,
            $this->lpaFormHelperResponseDto->getStatus()
        );
    }

    public function testGetMessages(): void
    {
        $this->assertEquals(
            $this->messages,
            $this->lpaFormHelperResponseDto->getMessages()
        );
    }

    public function testGetData(): void
    {
        $this->assertEquals(
            $this->data,
            $this->lpaFormHelperResponseDto->getData()
        );
    }

    public function testGetAdditionalData(): void
    {
        $this->assertEquals(
            $this->additionalData,
            $this->lpaFormHelperResponseDto->getAdditionalData()
        );
    }

    public function testConstructVariables(): void
    {
        $this->assertEquals(
            $this->variables,
            $this->lpaFormHelperResponseDto->constructFormVariables()
        );
    }

    public function testToArray(): void
    {
        $this->assertEquals(
            [
                'uuid' => $this->uuid,
                'form' => $this->form,
                'status' => 'success',
                'messages' => [],
                'data' => [],
                'additionalData' => [],
            ],
            $this->lpaFormHelperResponseDto->toArray()
        );
    }
}
