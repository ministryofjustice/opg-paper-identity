<?php

declare(strict_types=1);

namespace Application\Model\Entity;

use Application\Exceptions\NotImplementedException;
use Application\Exceptions\PropertyMatchException;
use Application\Validators\IsType;
use Application\Validators\LpaUidValidator;
use Exception;
use JsonSerializable;
use Laminas\Form\Annotation;
use Laminas\Form\Annotation\Validator;
use Laminas\Validator\Explode;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\Uuid;

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
     * @var "TELEPHONE"|"POSTOFFICE"|"VOUCHING"
     */
    #[Annotation\Required(false)]
    public ?string $idRoute = null;

    /**
     * @var "donor"|"certificateProvider"|"voucher"
     */
    #[Validator(NotEmpty::class)]
    public string $personType;

    /**
     * @var ?ClaimedIdentity
     */
    #[Annotation\Required(false)]
    #[Annotation\ComposedObject(ClaimedIdentity::class)]
    public ?ClaimedIdentity $claimedIdentity = null;

    /**
     * @var ?VouchingFor
     */
    #[Annotation\Required(false)]
    public ?VouchingFor $vouchingFor = null;

    /**
     * @var string[]
     */
    #[Annotation\Validator(Explode::class, options: ['validator' => ['name' => LpaUidValidator::class]])]
    public array $lpas;

    #[Annotation\ComposedObject(IdentityIQ::class)]
    public ?IdentityIQ $identityIQ = null;

    #[Annotation\Required(false)]
    #[Annotation\Validator(IsType::class, options: ['type' => 'boolean'])]
    #[Annotation\Validator(NotEmpty::class, options: [NotEmpty::NULL])]
    public bool $documentComplete = false;

    #[Annotation\Required(false)]
    #[Annotation\Validator(IsType::class, options: ['type' => 'boolean'])]
    #[Annotation\Validator(NotEmpty::class, options: [NotEmpty::NULL])]
    public ?bool $identityCheckPassed = null;

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

    #[Annotation\Required(false)]
    public ?CaseAssistance $caseAssistance = null;
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
            } elseif ($key === 'idMethodIncludingNation') {
                $instance->idMethodIncludingNation = IdMethodIncludingNation::fromArray($value);
            } elseif ($key === 'vouchingFor') {
                $instance->vouchingFor = VouchingFor::fromArray($value);
            } elseif ($key === 'claimedIdentity') {
                $instance->claimedIdentity = ClaimedIdentity::fromArray($value);
            } elseif ($key === 'caseAssistance') {
                $instance->caseAssistance = CaseAssistance::fromArray($value);
            } elseif ($key === 'identityIQ') {
                $instance->identityIQ = IdentityIQ::fromArray($value);
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
     *     idRoute: "TELEPHONE"|"POSTOFFICE"|"VOUCHING"|null,
     *     personType: "donor"|"certificateProvider"|"voucher",
     *     vouchingFor?: VouchingFor,
     *     lpas: string[],
     *     documentComplete: bool,
     *     identityCheckPassed: ?bool,
     *     yotiSessionId: string,
     *     counterService?: CounterService,
     *     idMethodIncludingNation?: IdMethodIncludingNation,
     *     caseProgress?: CaseProgress,
     *     caseAssistance?: CaseAssistance,
     *     claimedIdentity?: ClaimedIdentity
     * }
     */
    public function toArray(): array
    {
        $arr = [
            'id' => $this->id,
            'idRoute' => $this->idRoute,
            'personType' => $this->personType,
            'lpas' => $this->lpas,
            'documentComplete' => $this->documentComplete,
            'identityCheckPassed' => $this->identityCheckPassed,
            'yotiSessionId' => $this->yotiSessionId,
        ];

        if ($this->idMethodIncludingNation !== null) {
            $arr['idMethodIncludingNation'] = $this->idMethodIncludingNation;
        }

        if ($this->counterService !== null) {
            $arr['counterService'] = $this->counterService;
        }

        if ($this->caseProgress !== null) {
            $arr['caseProgress'] = $this->caseProgress;
        }

        if ($this->vouchingFor !== null) {
            $arr['vouchingFor'] = $this->vouchingFor;
        }

        if ($this->claimedIdentity !== null) {
            $arr['claimedIdentity'] = $this->claimedIdentity;
        }

        if ($this->caseAssistance !== null) {
            $arr['caseAssistance'] = $this->caseAssistance;
        }

        return $arr;
    }

    public function update(mixed $data): void
    {
        foreach ($data as $key => $value) {
            if ($key === 'counterService') {
                throw new NotImplementedException('counterService update function not yet implemented');
            } elseif ($key === 'caseProgress') {
                throw new NotImplementedException('caseProgress update function not yet implemented');
            } elseif ($key === 'kbvQuestions') {
                throw new NotImplementedException('kbvQuestions update function not yet implemented');
            } elseif ($key === 'iiqControl') {
                throw new NotImplementedException('iiqControl update function not yet implemented');
            } elseif ($key === 'idMethodIncludingNation') {
                throw new NotImplementedException('idMethodIncludingNation update function not yet implemented');
            } elseif ($key === 'vouchingFor') {
                throw new NotImplementedException('vouchingFor update function not yet implemented');
            } elseif ($key === 'claimedIdentity') {
                $this->claimedIdentity?->update($value);
            } elseif ($key === 'caseAssistance') {
                throw new NotImplementedException('caseAssistance update function not yet implemented');
            } elseif (property_exists($this, $key)) {
                $this->{$key} = $value;
            } else {
                throw new PropertyMatchException(sprintf('%s does not have property "%s"', $this::class, $key));
            }
        }
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
