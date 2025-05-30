<?php

declare(strict_types=1);

namespace Application\Helpers\DTO;

use Application\Enums\LpaStatusType;
use Laminas\Form\FormInterface;

class LpaFormHelperResponseDto
{
    public function __construct(
        private string $uuid,
        private FormInterface $form,
        private ?LpaStatusType $status,
        private array $messages,
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

    public function getStatus(): ?LpaStatusType
    {
        return $this->status;
    }

    public function getMessages(): array
    {
        return $this->messages;
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
            'messages' => $this->getMessages(),
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
            'messages' => $this->getMessages(),
            'data' => $this->getData(),
            'additionalData' => $this->getAdditionalData(),
        ];
    }
}
