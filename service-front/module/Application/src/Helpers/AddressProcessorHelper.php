<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Contracts\OpgApiServiceInterface;
use Application\Helpers\DTO\FormProcessorResponseDto;
use Laminas\Form\FormInterface;
use Laminas\Stdlib\Parameters;

class AddressProcessorHelper
{
    public function __construct(private array $address)
    {}

    public function getAddress(): array
    {
        return [
            'line1' => $this->address['line1'] ?? '',
            'line2' => $this->address['line2'] ?? '',
            'line3' => $this->address['line3'] ?? '',
            'town' => $this->address['town'] ?? '',
            'postcode' => $this->address['postcode'] ?? '',
            'country' => $this->address['country'] ?? '',
        ];
    }
}
