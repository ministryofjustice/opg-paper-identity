<?php

declare(strict_types=1);

namespace Application;

class ConfigProvider
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return require __DIR__ . '/../config/module.config.php';
    }
}
