<?php

declare(strict_types=1);

namespace Application\Model\Entity;

use Application\Model\IdMethod;
use Application\Validators\Enum;
use Application\Validators\IsType;
use Application\Validators\LpaUidValidator;
use Exception;
use JsonSerializable;
use Laminas\Form\Annotation;
use Laminas\Form\Annotation\Validator;
use Laminas\Validator\Explode;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\Regex;
use Laminas\Validator\Uuid;

/**
 * DTO for holding data required to make new case entry post
 * @psalm-suppress MissingConstructor
 * Needed here due to false positive from Laminas’s uninitialised properties
 * @psalm-suppress UnusedProperty
 */
class CaseData implements JsonSerializable
{
    #[Annotation\Required(false)]
    #[Annotation\Validator(NotEmpty::class, options: [NotEmpty::NULL])]
    #[Validator(Uuid::class)]
    public string $id;

    #[Validator(NotEmpty::class)]
    public string $personType;

    #[Annotation\Required(false)]
    #[Validator(Regex::class, options: ["pattern" => "/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", "messages" => [
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

    #[Annotation\Required(false)]
    #[Annotation\Validator(Enum::class, options: ['enum' => IdMethod::class])]
    public ?string $idMethod = null;

    /**
     * @var string[]
     */
    #[Annotation\Required(false)]
    public ?array $alternateAddress = [];

    #[Annotation\Required(false)]
    public ?string $selectedPostOfficeDeadline = null;


    #[Annotation\Required(false)]
    public ?string $selectedPostOffice = null;

    #[Annotation\Required(false)]
    public ?string $searchPostcode = null;

    #[Annotation\Required(false)]
    #[Validator(Uuid::class)]
    public ?string $sessionId = null;

    #[Annotation\Required(false)]
    #[Validator(Uuid::class)]
    public ?string $notifyAuthToken = null;

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(mixed $data): self
    {
        $instance = new self();

        foreach ($data as $key => $value) {
            if (! property_exists($instance, $key)) {
                throw new Exception(sprintf('%s does not have property "%s"', $instance::class, $key));
            }

            $instance->{$key} = $value;
        }

        return $instance;
    }

    /**
     * @returns array{
     *     personType: "donor"|"certificateProvider",
     *     firstName: string,
     *     lastName: string,
     *     dob: string,
     *     address: string[],
     *     lpas: string[],
     *     kbvQuestions?: string,
     *     documentComplete: bool,
     *     alternateAddress?: string[],
     *     selectedPostOfficeDeadline?:  string,
     *     selectedPostOffice?: string,
     *     searchPostcode?: string,
     *     idMethod?: string,
     *     sessionId?: string,
     *     notifyAuthToken?: string,
     *     kbvQuestions?: string[]
     * }
     */
    public function toArray(): array
    {
        $arr = [
            'id' => $this->id,
            'personType' => $this->personType,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'dob' => $this->dob,
            'address' => $this->address,
            'lpas' => $this->lpas,
            'documentComplete' => $this->documentComplete,
            'alternateAddress' => $this->alternateAddress,
            'selectedPostOfficeDeadline' => $this->selectedPostOfficeDeadline,
            'selectedPostOffice' => $this->selectedPostOffice,
            'searchPostcode' => $this->searchPostcode,
            'idMethod' => $this->idMethod,
            'sessionId' => $this->sessionId,
            'notifyAuthToken' => $this->notifyAuthToken
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
