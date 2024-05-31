<?php

declare(strict_types=1);

namespace Application\Model\Entity;

use Application\Validators\IsType;
use Application\Validators\LpaUidValidator;
use JsonSerializable;
use Laminas\Form\Annotation;
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
class CaseData implements JsonSerializable
{
    #[Validator(NotEmpty::class)]
    public string $personType;

    #[Validator(Regex::class, options: ["pattern" => "/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", "messages" => [
        Regex::NOT_MATCH => 'Please enter a valid date of birth in the format YYYY-MM-DD'
    ]])]
    public string $dob;

    #[Validator(NotEmpty::class)]
    public string $firstName;

    #[Validator(NotEmpty::class)]
    public string $lastName;

    /**
     * @var string[]
     */
    #[Validator(NotEmpty::class)]
    public array $address;

    /**
     * @var string[]
     */
    #[Annotation\Validator(Explode::class, options: ['validator' => ['name' => LpaUidValidator::class]])]
    public array $lpas;

    public ?string $kbvQuestions = null;

    #[Annotation\Required(false)]
    #[Annotation\Validator(IsType::class, options: ['type' => 'boolean'])]
    #[Annotation\Validator(NotEmpty::class, options: [NotEmpty::NULL])]
    public bool $documentComplete = false;

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

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
