<?php

declare(strict_types=1);

namespace Application\Validators;

use Laminas\Validator\AbstractValidator;
use Laminas\Validator\Exception\RuntimeException;

class Enum extends AbstractValidator
{
    final public const INVALID = 'invalid';

    /** @var array<string, string> */
    protected $messageTemplates = [
        self::INVALID => 'The value was not valid for %value%',
    ];

    /** @var ?class-string */
    protected ?string $enum = null;

    /**
     * @psalm-suppress PossiblyUnusedMethod
     * This is called from within laminas-form
     */
    public function setEnum(string $enum): static
    {
        if (! enum_exists($enum)) {
            throw new RuntimeException($enum . ' is not a valid enum');
        }

        $this->enum = $enum;

        return $this;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function isValid($value)
    {
        if (is_null($this->enum)) {
            throw new RuntimeException('Enum not configured in validator');
        }

        if (is_string($value) ? $this->enum::tryFrom($value) === null : ! ($value instanceof $this->enum)) {
            $this->error(self::INVALID, $this->enum);

            return false;
        }

        return true;
    }
}
