<?php

declare(strict_types=1);

namespace Application\Model\Entity;

use Laminas\Form\Annotation;

/**
 * @psalm-suppress PossiblyUnusedProperty
 */
class KBVQuestion extends Entity
{
    #[Annotation\Required(true)]
    public string $externalId = '';

    #[Annotation\Required(true)]
    public string $question = '';

    /**
     * @var string[]
     */
    #[Annotation\Required(true)]
    public array $prompts = [];

    #[Annotation\Required(true)]
    public bool $answered = false;

    /**
     * @param properties-of<self> $data
     */
    public static function fromArray(mixed $data): self
    {
        $instance = new self();

        foreach ($data as $key => $value) {
            if (! property_exists($instance, $key)) {
                throw new \Exception(sprintf('%s does not have property "%s"', $instance::class, $key));
            }

            $instance->{$key} = $value;
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
