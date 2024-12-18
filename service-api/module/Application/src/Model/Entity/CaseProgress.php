<?php

declare(strict_types=1);

namespace Application\Model\Entity;

use Exception;
use Laminas\Form\Annotation;
use Laminas\Form\Annotation\Validator;
use Laminas\Validator\NotEmpty;

/**
 * DTO for holding case progress data
 * @psalm-suppress MissingConstructor
 * Needed here due to false positive from Laminas’s uninitialised properties
 * @psalm-suppress UnusedProperty
 */
class CaseProgress extends Entity
{
    #[Annotation\Validator(NotEmpty::class, options: [NotEmpty::STRING])]
    public string $last_page;

    #[Validator(NotEmpty::class)]
    public string $timestamp;

    /**
     * @param properties-of<self> $data
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
     * @return properties-of<self>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
