<?php

declare(strict_types=1);

namespace Application\Model\Entity;

use Application\Validators\LpaUidValidator;
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
class CaseData
{
    #[Validator(NotEmpty::class)]
    private string $personType;

    #[Validator(Regex::class, options: ["pattern" => "/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/"])]
    private string $dob;

    #[Validator(NotEmpty::class)]
    private string $firstName;

    #[Validator(NotEmpty::class)]
    private string $lastName;

    /**
     * @var string[]
     */
    #[Validator(NotEmpty::class)]
    private array $address;

    /**
     * @var string[]
     */
    #[Annotation\Validator(Explode::class, options: ['validator' => ['name' => LpaUidValidator::class]])]
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
        $instance->dob = $data['dob'];
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
