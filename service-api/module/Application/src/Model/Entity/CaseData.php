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

    #[Annotation\Required(false)]
    #[Validator(Regex::class, options: ["pattern" => "/^NA$|^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", "messages" => [
        Regex::NOT_MATCH => 'Please enter a valid date of birth in the format YYYY-MM-DD'
    ]])]
    public ?string $dob;

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
     * @param array{personType: string, firstName: string, lastName: string, dob: ?string,
     *     lpas: array{}, address: array{} } $data
     */
    public static function fromArray(mixed $data): self
    {
        $instance = new self();
        $instance->personType = $data['personType'];
        $instance->firstName = $data['firstName'];
        $instance->lastName = $data['lastName'];
        $instance->dob = $data['dob'] ?? 'NA';
        $instance->lpas = $data['lpas'];
        $instance->address = $data['address'];

        return $instance;
    }

    /**
     * @returns array{
     *     personType: "donor"|"certificateProvider",
     *     firstName: string,
     *     lastName: string,
     *     dob: ?string,
     *     address: string[],
     *     lpas: string[],
     *     kbvQuestions?: string,
     *     documentComplete: bool
     * }
     */
    public function toArray(): array
    {
        $arr = [
            'personType' => $this->personType,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'dob' => $this->dob,
            'address' => $this->address,
            'lpas' => $this->lpas,
            'documentComplete' => $this->documentComplete,
        ];

        if ($this->kbvQuestions !== null) {
            $arr['kbvQuestions'] = $this->kbvQuestions;
        }

        return $arr;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
