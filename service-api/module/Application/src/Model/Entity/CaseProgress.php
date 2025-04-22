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
    #[Annotation\Required(false)]
    #[Annotation\Validator(NotEmpty::class, options: [NotEmpty::NULL])]
    public ?AbandonedFlow $abandonedFlow = null;

    #[Annotation\Required(false)]
    #[Annotation\Validator(NotEmpty::class, options: [NotEmpty::NULL])]
    public ?DocCheck $docCheck = null;

    #[Annotation\Required(false)]
    #[Annotation\Validator(NotEmpty::class, options: [NotEmpty::NULL])]
    public ?Kbvs $kbvs = null;

    #[Annotation\Required(false)]
    #[Annotation\Validator(NotEmpty::class, options: [NotEmpty::NULL])]
    public ?FraudScore $fraudScore = null;


    // TODO: can we get round the need for this??
    #[Annotation\Required(false)]
    #[Annotation\Validator(NotEmpty::class, options: [NotEmpty::NULL])]
    public ?array $restrictedMethods = [];

    public static function fromArray(mixed $data): self
    {
        $instance = new self();

        foreach ($data as $key => $value) {
            if ($key === 'abandonedFlow') {
                $instance->abandonedFlow = is_array($value) ? AbandonedFlow::fromArray($value) : null;
            } elseif ($key === 'docCheck') {
                $instance->docCheck = is_array($value) ? DocCheck::fromArray($value) : null;
            } elseif ($key === 'kbvs') {
                $instance->kbvs = is_array($value) ? Kbvs::fromArray($value) : null;
            } elseif ($key === 'fraudScore') {
                $instance->fraudScore = is_array($value) ? FraudScore::fromArray($value) : null;
            } elseif (property_exists($instance, $key)) {
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
