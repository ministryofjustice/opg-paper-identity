<?php

declare(strict_types=1);

namespace Application\Model\Entity;

use Laminas\Form\Annotation;
use Exception;

class IdentityIQ extends Entity
{
    /**
     * @var KBVQuestion[]
     */
    #[Annotation\ComposedObject(KBVQuestion::class, isCollection: true)]
    public array $kbvQuestions = [];

    public ?IIQControl $iiqControl = null;

    /**
     * @psalm-suppress PossiblyUnusedMethod
     * @param array<string, mixed> $data
     */
    public static function fromArray(mixed $data): self
    {
        $instance = new self();

        foreach ($data as $key => $value) {
            if ($key === 'kbvQuestions') {
                $instance->kbvQuestions = array_map(fn(array $question) => KBVQuestion::fromArray($question), $value);
            } elseif ($key === 'iiqControl') {
                $instance->iiqControl = IIQControl::fromArray($value);
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
