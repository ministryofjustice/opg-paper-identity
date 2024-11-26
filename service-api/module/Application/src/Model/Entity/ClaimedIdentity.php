<?php

declare(strict_types=1);

namespace Application\Model\Entity;

use Exception;
use Laminas\Form\Annotation\Validator;
use Laminas\Validator\NotEmpty;
use Laminas\Form\Annotation;
use Laminas\Validator\Regex;

/**
 * DTO for holding claimed identity data
 */
class ClaimedIdentity extends Entity
{
    #[Annotation\Required(false)]
    #[Validator(Regex::class, options: ["pattern" => "/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", "messages" => [
        Regex::NOT_MATCH => 'Please enter a valid date of birth in the format YYYY-MM-DD'
    ]])]
    public ?string $dob = null;

    #[Validator(NotEmpty::class, options: [NotEmpty::STRING])]
    public ?string $firstName = null;

    #[Validator(NotEmpty::class, options: [NotEmpty::STRING])]
    public ?string $lastName = null;

    /**
     * @var array{
     *   line1: string,
     *   line2?: string,
     *   line3?: string,
     *   town?: string,
     *   postcode: string,
     *   country?: string,
     * }|null
     */
    #[Annotation\Required(false)]
    public ?array $address = null;

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
