<?php

declare(strict_types=1);

namespace Application\Views;

use Laminas\Http\PhpEnvironment\Request;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TwigExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        public readonly bool $debug,
        public readonly Request $request
    ) {
    }

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

    public function getGlobals(): array
    {
        return [
            'SIRIUS_PUBLIC_URL' => getenv("SIRIUS_PUBLIC_URL"),
            'DEBUG' => $this->debug,
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('path', [$this, 'getPath']),
        ];
    }

    public function getPath(): string
    {
        return $this->request->getRequestUri();
    }
}
