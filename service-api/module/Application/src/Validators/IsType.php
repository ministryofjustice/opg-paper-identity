<?php

declare(strict_types=1);

namespace Application\Validators;

use Laminas\Validator\AbstractValidator;

class IsType extends AbstractValidator
{
    public const INVALID_TYPE = 'invalid_type';

    protected array $messageTemplates = [
        self::INVALID_TYPE => 'The type is not valid',
    ];

    /**
     * @var string[]
     */
    public readonly array $allowedTypes;

    /**
     * @param {types: string[]|string} $options
     */
    public function __construct(array $options)
    {
        if (is_array($options['type'])) {
            $this->allowedTypes = $options['type'];
        } elseif (is_string($options['type'])) {
            $this->allowedTypes = [$options['type']];
        } else {
            $this->allowedTypes = [];
        }
    }

    public function isValid(mixed $value): bool
    {
        $type = gettype($value);

        if (! in_array($type, $this->allowedTypes)) {
            $this->error(self::INVALID_TYPE);
            return false;
        }

        return true;
    }
}
