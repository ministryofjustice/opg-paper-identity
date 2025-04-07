<?php

declare(strict_types=1);

namespace Application\Model\Entity;

use Application\Enums\DocumentType;
use Application\Enums\IdRoute;
use Laminas\Form\Annotation;
use Laminas\Form\Annotation\Validator;
use Laminas\Validator\NotEmpty;
use Application\Validators\Enum;

/**
 * DTO for holding ID method data
 * @psalm-suppress MissingConstructor
 * Needed here due to false positive from Laminas’s uninitialised properties
 * @psalm-suppress UnusedProperty
 */
class IdMethod extends Entity
{
    #[Annotation\Required(false)]
    #[Annotation\Validator(Enum::class, options: [DocumentType::class])]
    public ?string $doc_type;

    #[Annotation\Required(false)]
    #[Annotation\Validator(Enum::class, options: [IdRoute::class])]
    public string $id_route;

    #[Annotation\Required(false)]
    #[Annotation\Validator(NotEmpty::class, options: [NotEmpty::NULL])]
    public string $id_country;

    #[Annotation\Required(false)]
    #[Annotation\Validator(NotEmpty::class, options: [NotEmpty::NULL])]
    public ?string $dwp_id_correlation;

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
