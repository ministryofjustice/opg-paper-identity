<?php

declare(strict_types=1);

namespace Application\Helpers\DTO;

use Laminas\Form\FormInterface;

class LpaFormHelperResponseDto
{
    public function __construct(
        private string $uuid,
        private FormInterface $form,
        private string $lpaStatus,
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

    public function getLpaStatus(): string
    {
        return $this->lpaStatus;
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
            'lpa_status' => $this->getLpaStatus(),
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
            'lpa_status' => $this->getLpaStatus(),
            'message' => $this->getMessage(),
            'data' => $this->getData(),
            'additionalData' => $this->getAdditionalData(),
        ];
    }
}
