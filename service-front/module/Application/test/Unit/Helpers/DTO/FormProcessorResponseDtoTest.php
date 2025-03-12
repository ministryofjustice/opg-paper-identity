<?php

declare(strict_types=1);

namespace ApplicationTest\Unit\Helpers\DTO;

use Application\Helpers\DTO\FormProcessorResponseDto;
use Laminas\Form\Annotation\AttributeBuilder;
use Laminas\Form\FormInterface;
use PHPUnit\Framework\TestCase;

class FormProcessorResponseDtoTest extends TestCase
{
    private FormProcessorResponseDto $formProcessorResponseDto;

    private string $uuid;

    private FormInterface $form;

    private string $template;

    private array $variables;

    private string $redirect;

    public function setUp(): void
    {
        parent::setUp();

        $this->form = (new AttributeBuilder())->createForm(TestValidator::class);
        $this->template = 'application/test';
        $this->uuid = "9130a21e-6e5e-4a30-8b27-76d21b747e60";
        $this->redirect = "test";

        $this->variables = [];

        $this->formProcessorResponseDto = new FormProcessorResponseDto(
            $this->uuid,
            $this->form,
            $this->template,
            $this->variables,
            $this->redirect
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

    public function testGetRedirect(): void
    {
        $this->assertEquals(
            $this->redirect,
            $this->formProcessorResponseDto->getRedirect()
        );
    }

    public function testToArray(): void
    {
        $this->assertEquals(
            [
                'uuid' => $this->uuid,
                'form' => $this->form,
                'template' => $this->template,
                'variables' => [],
                'redirect' => 'test'
            ],
            $this->formProcessorResponseDto->toArray()
        );
    }
}
