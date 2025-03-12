<?php

declare(strict_types=1);

namespace ApplicationTest\Unit\Helpers\DTO;

use Application\Helpers\DTO\FormProcessorRequestDto;
use Laminas\Form\Annotation\AttributeBuilder;
use Laminas\Form\FormInterface;
use Laminas\Stdlib\Parameters;
use PHPUnit\Framework\TestCase;

class FormProcessorRequestDtoTest extends TestCase
{
    private FormProcessorRequestDto $formProcessorRequestDto;

    private string $uuid;

    private FormInterface $form;

    private Parameters $params;

    private array $templates;

    public function setUp(): void
    {
        parent::setUp();

        $this->form = (new AttributeBuilder())->createForm(TestValidator::class);
        $this->params = new Parameters(['test' => 'test']);
        $this->templates = [
            'default' => 'application/test',
        ];
        $this->uuid = "9130a21e-6e5e-4a30-8b27-76d21b747e60";

        $this->formProcessorRequestDto = new FormProcessorRequestDto(
            $this->uuid,
            $this->params,
            $this->form,
            $this->templates
        );
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(
            $this->uuid,
            $this->formProcessorRequestDto->getUuid()
        );
    }

    public function testGetForm(): void
    {
        $this->assertEquals(
            $this->form,
            $this->formProcessorRequestDto->getForm()
        );
    }

    public function testGetParams(): void
    {
        $this->assertEquals(
            $this->params,
            $this->formProcessorRequestDto->getFormData()
        );
    }

    public function testGetTemplates(): void
    {
        $this->assertEquals(
            $this->templates,
            $this->formProcessorRequestDto->getTemplates()
        );
    }

    public function testToArray(): void
    {
        $this->assertEquals(
            [
                'uuid' => $this->uuid,
                'formData' => $this->params,
                'form' => $this->form,
                'templates' => $this->templates,
            ],
            $this->formProcessorRequestDto->toArray()
        );
    }
}
