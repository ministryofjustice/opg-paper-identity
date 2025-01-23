<?php

declare(strict_types=1);

namespace Application\Model\Entity;

use Application\Enums\IdRoute;
use Exception;
use Laminas\Form\Annotation;
use Laminas\Form\Annotation\Validator;
use Laminas\Validator\NotEmpty;
use Application\Validators\Enum;
use Application\Enums\IdMethod;

/**
 * DTO for holding ID method data
 * @psalm-suppress MissingConstructor
 * Needed here due to false positive from Laminas’s uninitialised properties
 * @psalm-suppress UnusedProperty
 */
class IdMethodIncludingNation extends Entity
{
    #[Annotation\Required(false)]
    #[Annotation\Validator(Enum::class, options: ['enum' => IdMethod::class])]
    public string $id_method;

    #[Annotation\Required(false)]
    #[Annotation\Validator(Enum::class, options: [IdRoute::class])]
    public string $id_route;

    #[Annotation\Required(false)]
    #[Annotation\Validator(NotEmpty::class, options: [NotEmpty::NULL])]
    public string $id_country;

    /**
     * @param properties-of<self> $data
     */
    public static function fromArray(mixed $data): self
    {
        $instance = new self();

        foreach ($data as $key => $value) {
            if (property_exists($instance, $key)) {
                $instance->{$key} = $value;
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
