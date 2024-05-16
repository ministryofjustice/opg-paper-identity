<?php

declare(strict_types=1);

namespace Application\Views;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;

class TwigExtension extends AbstractExtension implements GlobalsInterface
{
    public function getFilters()
    {
        $prefix = getenv("PREFIX");
        if (! is_string($prefix)) {
            $prefix = '';
        }

        return [
            new TwigFilter('basepath', fn (string $str) => $prefix . $str),
        ];
    }

    public function getGlobals():array {
        return [
            'SIRIUS_PUBLIC_URL' => getenv("SIRIUS_PUBLIC_URL"),
        ];
    }
}
