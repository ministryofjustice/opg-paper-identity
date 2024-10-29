<?php

declare(strict_types=1);

namespace Application\Model\Entity;

use Application\Model\IdMethod;
use Application\Model\Entity\CounterService;
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
use Application\Model\Entity\CaseProgress;

/**
 * DTO for holding data required to make new case entry post
 * @psalm-suppress MissingConstructor
 * Needed here due to false positive from Laminas’s uninitialised properties
 */
class CaseData implements JsonSerializable
{
    #[Annotation\Required(false)]
    #[Annotation\Validator(NotEmpty::class, options: [NotEmpty::NULL])]
    #[Validator(Uuid::class)]
    public string $id;

    /**
     * @var "donor"|"certificateProvider"
     */
    #[Validator(NotEmpty::class)]
    public string $personType;

    #[Annotation\Required(false)]
    #[Validator(Regex::class, options: ["pattern" => "/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", "messages" => [
        Regex::NOT_MATCH => 'Please enter a valid date of birth in the format YYYY-MM-DD'
    ]])]
    public ?string $dob;

    private function formattedDOB(): string
    {
        if (! isset($this->dob)) {
            return '';
        }
        return date_format(date_create($this->dob), "d F Y");
    }

    #[Validator(NotEmpty::class)]
    public string $firstName;

    #[Validator(NotEmpty::class)]
    public string $lastName;

    /**
     * @var array{
     *   line1: string,
     *   line2?: string,
     *   line3?: string,
     *   town?: string,
     *   postcode: string,
     *   country?: string,
     * }
     */
    #[Validator(NotEmpty::class)]
    public array $address;

    /**
     * @var string[]
     */
    #[Annotation\Validator(Explode::class, options: ['validator' => ['name' => LpaUidValidator::class]])]
    public array $lpas;

    /**
     * @var KBVQuestion[]
     */
    #[Annotation\ComposedObject(KBVQuestion::class, isCollection: true)]
    public array $kbvQuestions = [];

    public ?IIQControl $iiqControl = null;

    #[Annotation\Required(false)]
    #[Annotation\Validator(IsType::class, options: ['type' => 'boolean'])]
    #[Annotation\Validator(NotEmpty::class, options: [NotEmpty::NULL])]
    public bool $documentComplete = false;

    /**
     * @var string[]
     */
    #[Annotation\Required(false)]
    public ?array $alternateAddress = [];

    #[Annotation\Required(false)]
    public ?string $searchPostcode = null;

    #[Annotation\Required(false)]
    #[Annotation\Validator(Uuid::class)]
    //Due to dynamodb quarks, due to index this always need to have a value
    public string $yotiSessionId = '00000000-0000-0000-0000-000000000000';

    #[Annotation\Required(false)]
    #[Annotation\ComposedObject(CounterService::class)]
    public ?CounterService $counterService = null;

    /**
     * @var ?IdMethodIncludingNation
     */
    #[Annotation\Required(false)]
    public ?IdMethodIncludingNation $idMethodIncludingNation = null;

    #[Annotation\Required(false)]
    public ?CaseProgress $caseProgress = null;

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(mixed $data): self
    {
        $instance = new self();

        foreach ($data as $key => $value) {
            if ($key === 'counterService') {
                $instance->counterService = CounterService::fromArray($value);
            } elseif ($key === 'caseProgress') {
                $instance->caseProgress = CaseProgress::fromArray($value);
            } elseif ($key === 'kbvQuestions') {
                $instance->kbvQuestions = array_map(fn(array $question) => KBVQuestion::fromArray($question), $value);
            } elseif ($key === 'iiqControl') {
                $instance->iiqControl = IIQControl::fromArray($value);
            } elseif ($key === 'idMethodIncludingNation') {
                $instance->idMethodIncludingNation = IdMethodIncludingNation::fromArray($value);
            } elseif (property_exists($instance, $key)) {
                $instance->{$key} = $value;
            } else {
                throw new Exception(sprintf('%s does not have property "%s"', $instance::class, $key));
            }
        }

        return $instance;
    }

    /**
     * @return array{
     *     id: string,
     *     personType: "donor"|"certificateProvider",
     *     firstName: string,
     *     lastName: string,
     *     dob: ?string,
     *     formatted_dob: ?string,
     *     address: array{
     *       line1: string,
     *       line2?: string,
     *       line3?: string,
     *       town?: string,
     *       postcode: string,
     *       country?: string,
     *     },
     *     lpas: string[],
     *     kbvQuestions: KBVQuestion[],
     *     iiqControl?: IIQControl,
     *     documentComplete: bool,
     *     alternateAddress: ?string[],
     *     searchPostcode: ?string,
     *     yotiSessionId: string,
     *     counterService?: CounterService,
     *     idMethodIncludingNation?: IdMethodIncludingNation,
     *     caseProgress?: CaseProgress,
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
            'formatted_dob' => $this->formattedDOB(),
            'address' => $this->address,
            'lpas' => $this->lpas,
            'documentComplete' => $this->documentComplete,
            'alternateAddress' => $this->alternateAddress,
            'searchPostcode' => $this->searchPostcode,
            'yotiSessionId' => $this->yotiSessionId,
            'kbvQuestions' => $this->kbvQuestions,
        ];

        if ($this->idMethodIncludingNation !== null) {
            $arr['idMethodIncludingNation'] = $this->idMethodIncludingNation;
        }

        if ($this->counterService !== null) {
            $arr['counterService'] = $this->counterService;
        }

        if ($this->iiqControl !== null) {
            $arr['iiqControl'] = $this->iiqControl;
        }

        if ($this->caseProgress !== null) {
            $arr['caseProgress'] = $this->caseProgress;
        }

        return $arr;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
