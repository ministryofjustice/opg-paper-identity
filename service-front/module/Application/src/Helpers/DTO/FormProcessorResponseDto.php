<?php

declare(strict_types=1);

namespace Application\Helpers\DTO;

use Laminas\Form\FormInterface;

class FormProcessorResponseDto
{
    public function __construct(
        private string $uuid,
        private FormInterface $form,
        private array $responseData,
        private string $template,
        private array $variables = [],
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getForm(): FormInterface
    {
        return $this->form;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function getVariables(): array
    {
        return $this->variables;
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'form' => $this->form,
            'responseData' => $this->responseData,
            'template' => $this->template,
            'variables' => $this->variables,
        ];
    }
}
