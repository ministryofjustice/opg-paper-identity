<?php

declare(strict_types=1);

namespace Application\Helpers\DTO;

use Laminas\Form\FormInterface;

class LpaFormHelperResponseDto
{
    public function __construct(
        private string $uuid,
        private FormInterface $form,
        private string $status,
        private string $message,
        private array $data = [],
        private array $additionalData = [],
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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getAdditionalData(): array
    {
        return $this->additionalData;
    }

    public function constructFormVariables(): array
    {
        return [
            'status' => $this->getStatus(),
            'message' => $this->getMessage(),
            'data' => $this->getData(),
            'additionalData' => $this->getAdditionalData(),
        ];
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->getUuid(),
            'form' => $this->getForm(),
            'status' => $this->getStatus(),
            'message' => $this->getMessage(),
            'data' => $this->getData(),
            'additionalData' => $this->getAdditionalData(),
        ];
    }
}
