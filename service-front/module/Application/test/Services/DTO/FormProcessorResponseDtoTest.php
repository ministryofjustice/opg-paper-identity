<?php

declare(strict_types=1);

namespace ApplicationTest\Services\DTO;

use Laminas\Form\Annotation\AttributeBuilder;
use Laminas\Form\FormInterface;
use Laminas\Stdlib\Parameters;
use PHPUnit\Framework\TestCase;
use Application\Services\DTO\FormProcessorResponseDto;

class FormProcessorResponseDtoTest extends TestCase
{
    private FormProcessorResponseDto $formProcessorResponseDto;

    private string $uuid;

    private FormInterface $form;

    private array $responseData;

    private string $template;

    private array $variables;

    public function setUp(): void
    {
        parent::setUp();

        $this->form = (new AttributeBuilder())->createForm(TestValidator::class);
        $this->template = 'application/test';
        $this->uuid = "9130a21e-6e5e-4a30-8b27-76d21b747e60";

        $this->responseData = [];
        $this->variables = [];

        $this->formProcessorResponseDto = new FormProcessorResponseDto(
            $this->uuid,
            $this->form,
            $this->responseData,
            $this->template,
            $this->variables
        );
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(
            $this->uuid,
            $this->formProcessorResponseDto->getUuid()
        );
    }

    public function testGetForm(): void
    {
        $this->assertEquals(
            $this->form,
            $this->formProcessorResponseDto->getForm()
        );
    }

    public function testGetResponseData(): void
    {
        $this->assertEquals(
            $this->responseData,
            $this->formProcessorResponseDto->getResponseData()
        );
    }

    public function testGetTemplates(): void
    {
        $this->assertEquals(
            $this->template,
            $this->formProcessorResponseDto->getTemplate()
        );
    }

    public function testGetVariables(): void
    {
        $this->assertEquals(
            $this->variables,
            $this->formProcessorResponseDto->getVariables()
        );
    }

    public function testToArray(): void
    {
        $this->assertEquals(
            [
                'uuid' => $this->uuid,
                'form' => $this->form,
                'responseData' => $this->responseData,
                'template' => $this->template,
                'variables' => [],
            ],
            $this->formProcessorResponseDto->toArray()
        );
    }
}
