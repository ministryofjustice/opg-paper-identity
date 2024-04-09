<?php

declare(strict_types=1);

namespace Application\Model\Entity;

use Application\Validators\LPAValidator;
use Laminas\Form\Annotation;
use JsonSerializable;
use Laminas\Form\Annotation\AttributeBuilder;
use Laminas\Form\Annotation\Validator;
use Laminas\Validator\InArray;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\Regex;

/**
 * DTO for holding data required to make new case entry post
 */
class CaseData implements JsonSerializable
{
    #[Validator(InArray::class, options: ["haystack" => ['passport', 'drivinglicense', 'nino']])]
    private string $verifyMethod;

    #[Validator(NotEmpty::class)]
    private string $donorType;

    #[Validator(Regex::class, options: ["pattern" => "/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/"])]
    private string $dob;

    #[Validator(NotEmpty::class)]
    private string $firstName;

    #[Validator(NotEmpty::class)]
    private string $lastName;

    #[Validator(Regex::class, options: ["pattern" => "/M(-([0-9A-Z]){4}){3}/"])]
    private string $lpa1;

    #[Annotation\Validator(LPAValidator::class)]
    private ?string $lpa2;

    #[Annotation\Validator(LPAValidator::class)]
    private ?string $lpa3;

    #[Annotation\Validator(LPAValidator::class)]
    private ?string $lpa4;

    /**
     * Factory method
     *
     * @param array{verifyMethod: ?string, donorType: ?string, firstName: string, lastName: string, dob: string,
     *     lpas: array{lpa1: ?string, lpa2: ?string, lpa3: ?string, lpa4: ?string }} $data
     */
    public static function fromArray(mixed $data): self
    {
        $instance = new self();
        $instance->verifyMethod = $data['verifyMethod'] ?? null;
        $instance->donorType = $data['donorType'] ?? null;
        $instance->firstName = $data['firstName'] ?? null;
        $instance->lastName = $data['lastName'] ?? null;
        $instance->dob = $data['dob'] ?? null;
        $instance->lpa1 = $data['lpas'][0] ?? null;
        $instance->lpa2 = $data['lpas'][1] ?? null;
        $instance->lpa3 = $data['lpas'][2] ?? null;
        $instance->lpa4 = $data['lpas'][3] ?? null;

        return $instance;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'verifyMethod' => $this->verifyMethod,
            'donorType' => $this->donorType,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'dob'   => $this->dob,
            'lpa1' => $this->lpa1,
            'lap2' => $this->lpa2,
            'lpa3' => $this->lpa3,
            'lpa4' => $this->lpa4,
        ];
    }

    public function isValid(): bool
    {
        return (new AttributeBuilder())
            ->createForm(get_class($this))
            ->setData(get_object_vars($this))
            ->isValid();
    }
}
