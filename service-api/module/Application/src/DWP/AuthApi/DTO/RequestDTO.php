<?php

declare(strict_types=1);

namespace Application\DWP\AuthApi\DTO;

class RequestDTO
{
    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $bundle,
        private readonly string $privateKey,
    ) {
    }

    /**
     * @return string
     */
    public function clientId(): string
    {
        return $this->clientId;
    }

    /**
     * @return string
     */
    public function clientSecret(): string
    {
        return $this->clientSecret;
    }

    /**
     * @return string
     */
    public function bundle(): string
    {
        return $this->bundle;
    }

    /**
     * @return string
     */
    public function privateKey(): string
    {
        return $this->privateKey;
    }

    public function toArray(): array
    {
        return [
            'clientId' => $this->clientId(),
            'clientSecret' => $this->clientSecret()
        ];
    }
}
