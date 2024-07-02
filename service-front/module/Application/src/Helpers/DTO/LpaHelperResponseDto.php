<?php

declare(strict_types=1);

namespace Application\Helpers\DTO;

use Laminas\Form\FormInterface;

class LpaHelperResponseDto
{
    public function __construct(
        private string $uuid,
        private FormInterface $form,
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

    public function getVariables(): array
    {
        return $this->variables;
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'form' => $this->form,
            'variables' => $this->variables,
        ];
    }
}
