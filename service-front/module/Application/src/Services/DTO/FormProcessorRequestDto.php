<?php

declare(strict_types=1);

namespace Application\Services\DTO;

use Laminas\Form\FormInterface;
use Laminas\Stdlib\Parameters;

class FormProcessorRequestDto
{
    public function __construct(
        private string $uuid,
        private Parameters $formData,
        private FormInterface $form,
        private array $templates = []
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getFormData(): Parameters
    {
        return $this->formData;
    }

    public function getForm(): FormInterface
    {
        return $this->form;
    }

    public function getTemplates(): array
    {
        return $this->templates;
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'formData' => $this->formData,
            'form' => $this->form,
            'templates' => $this->templates,
        ];
    }
}
