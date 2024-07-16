<?php

declare(strict_types=1);

namespace Application\Helpers\DTO;

use Laminas\Form\FormInterface;

class FormProcessorResponseDto
{
    public function __construct(
        private string $uuid,
        private FormInterface $form,
        private string $template,
        private array $variables = [],
        private string|null $redirect = null
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

    public function getRedirect(): ?string
    {
        return $this->redirect;
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'form' => $this->form,
            'template' => $this->template,
            'variables' => $this->variables,
            'redirect' => $this->redirect
        ];
    }
}
