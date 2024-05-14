<?php

declare(strict_types=1);

namespace Application\Services\DTO;

use Laminas\Form\FormInterface;
use Laminas\Stdlib\Parameters;

class FormProcessorResponseDto
{
    public function __construct(
        private string $uuid,
        private ?FormInterface $form = null,
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

    public function getResponseData(): array
    {
        return $this->responseData;
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
