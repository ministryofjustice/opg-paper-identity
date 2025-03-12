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
            new TwigFilter('format_status', fn (string $status) => match ($status) {
                'draft' => 'Draft',
                'in-progress' => 'In progress',
                'statutory-waiting-period' => 'Statutory waiting period',
                'do-not-register' => 'Do not register',
                'expired' => 'Expired',
                'registered' => 'Registered',
                'cannot-register' => 'Cannot register',
                'cancelled' => 'Cancelled',
                'de-registered' => 'De-registered',
                'suspended' => 'Suspended',
                default => $status,
            })
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
