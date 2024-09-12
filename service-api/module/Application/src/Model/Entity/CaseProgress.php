<?php

declare(strict_types=1);

namespace Application\Model\Entity;

use Exception;
use JsonSerializable;
use Laminas\Form\Annotation;
use Laminas\Form\Annotation\Validator;
use Laminas\Validator\NotEmpty;

/**
 * DTO for holding case progress data
 * @psalm-suppress MissingConstructor
 * Needed here due to false positive from Laminas’s uninitialised properties
 * @psalm-suppress UnusedProperty
 */
class CaseProgress implements JsonSerializable
{
    #[Annotation\Required(false)]
    #[Annotation\Validator(NotEmpty::class, options: [NotEmpty::NULL])]
    public string $last_page;

    #[Validator(NotEmpty::class)]
    public string $timestamp;

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(mixed $data): self
    {
        $instance = new self();

        foreach ($data as $key => $value) {
            if (property_exists($instance, $key)) {
                $instance->{$key} = $value;
            } else {
                throw new Exception(sprintf('%s does not have property "%s"', $instance::class, $key));
            }
        }
        return $instance;
    }

    /**
     * @returns array{
     *     last_page: string,
     *     timestamp: string,
     * }
     */
    public function toArray(): array
    {
        $arr = [
            'last_page' => $this->last_page,
            'timestamp' => $this->timestamp,
        ];
        return $arr;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
