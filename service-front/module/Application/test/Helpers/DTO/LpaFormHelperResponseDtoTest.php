<?php

declare(strict_types=1);

namespace ApplicationTest\Helpers\DTO;

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

    private array $addtionalData;

    private string $lpa_status;

    private string $message;

    public function setUp(): void
    {
        parent::setUp();

        $this->form = (new AttributeBuilder())->createForm(TestValidator::class);
        $this->uuid = "9130a21e-6e5e-4a30-8b27-76d21b747e60";

        $this->lpa_status = 'OK';
        $this->message = "";
        $this->data = [];
        $this->addtionalData = [];
        $this->variables = [
            'lpa_status' => 'OK',
            'message' => '',
            'data' => [],
            'additionalData' => [],
        ];

        $this->lpaFormHelperResponseDto = new LpaFormHelperResponseDto(
            $this->uuid,
            $this->form,
            $this->lpa_status,
            $this->message,
            $this->data,
            $this->addtionalData,
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
            $this->lpa_status,
            $this->lpaFormHelperResponseDto->getLpaStatus()
        );
    }

    public function testGetMessage(): void
    {
        $this->assertEquals(
            $this->message,
            $this->lpaFormHelperResponseDto->getMessage()
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
            $this->addtionalData,
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
                'lpa_status' => 'OK',
                'message' => '',
                'data' => [],
                'additionalData' => [],
            ],
            $this->lpaFormHelperResponseDto->toArray()
        );
    }
}
