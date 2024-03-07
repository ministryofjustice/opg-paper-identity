<?php

declare(strict_types=1);

namespace Application\Views;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TwigExtension extends AbstractExtension
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
}
