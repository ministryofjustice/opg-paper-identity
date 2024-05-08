<?php

declare(strict_types=1);

namespace Application\Model\Entity;

use Application\Validators\LPAValidator;
use Laminas\Form\Annotation;
use Laminas\Form\Annotation\AttributeBuilder;
use Laminas\Form\Annotation\Validator;
use Laminas\Validator\Explode;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\Regex;

/**
 * DTO for holding data required to make new case entry post
 * @psalm-suppress MissingConstructor
 * Needed here due to false positive from Laminas’s uninitialised properties
 * @psalm-suppress UnusedProperty
 */
class CpCaseData
{
    #[Validator(NotEmpty::class)]
    private string $personType;

    #[Validator(NotEmpty::class)]
    private string $firstName;

    #[Validator(NotEmpty::class)]
    private string $lastName;

    private string|null $dob;

    /**
     * @var string[]
     */
    #[Validator(NotEmpty::class)]
    private array $address;

    /**
     * @var string[]
     */
    #[Annotation\Validator(Explode::class, options: ['validator' => ['name' => LPAValidator::class]])]
    private array $lpas;

    /**
     * Factory method
     *
     * @param array{personType: string, firstName: string, lastName: string, dob: string,
     *     lpas: array{}, address: array{} } $data
     */
    public static function fromArray(mixed $data): self
    {
        $instance = new self();
        $instance->personType = $data['personType'];
        $instance->firstName = $data['firstName'];
        $instance->lastName = $data['lastName'];
        $instance->dob = null;
        $instance->lpas = $data['lpas'];
        $instance->address = $data['address'];

        return $instance;
    }
    public function isValid(): bool
    {
        return (new AttributeBuilder())
            ->createForm(get_class($this))
            ->setData(get_object_vars($this))
            ->isValid();
    }

    public function toArray(): array
    {
        return [
            'personType' => $this->personType,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'dob' => $this->dob,
            'address' => $this->address,
            'lpas' => $this->lpas,
        ];
    }
}
