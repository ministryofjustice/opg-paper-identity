<?php

declare(strict_types=1);

namespace Application\Forms;

use Application\Validators\PassportDateValidator;
use Laminas\Form\Annotation;
use Laminas\Hydrator\ObjectPropertyHydrator;

/**
 * @psalm-suppress MissingConstructor
 * @implements FormTemplate<array{
 *   passport_issued_year: string,
 *   passport_issued_month: string,
 *   passport_issued_day: string,
 *   passport_date?: string
 * }>
 */
#[Annotation\Hydrator(ObjectPropertyHydrator::class)]
class PassportDatePo implements FormTemplate
{
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    #[Annotation\Validator(PassportDateValidator::class, options: ['expiry_allowance' => '+18 month'])]
    public mixed $passport_date;
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    public string $passport_issued_day;
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    public string $passport_issued_month;
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    public string $passport_issued_year;
}
