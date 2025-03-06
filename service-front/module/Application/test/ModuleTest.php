<?php

declare(strict_types=1);

namespace ApplicationTest;

use Application\Module;
use PHPUnit\Framework\TestCase;

class ModuleTest extends TestCase
{
    public function testProvidesConfig(): void
    {
        $module = new Module();
        $config = $module->getConfig();

        self::assertArrayHasKey('router', $config);
        self::assertArrayHasKey('controllers', $config);
    }
}
