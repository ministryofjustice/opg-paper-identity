<?php

declare(strict_types=1);

namespace Application\Forms;

use Laminas\Form\Annotation;
use Laminas\Hydrator\ObjectPropertyHydrator;
use Laminas\Validator\NotEmpty;
use Laminas\Filter\Callback;

use function PHPUnit\Framework\isEmpty;

/**
 * @psalm-suppress MissingConstructor
 * @implements FormTemplate<array{postoffice: string}>
 */
#[Annotation\Hydrator(ObjectPropertyHydrator::class)]
class PostOfficeSelect implements FormTemplate
{
    // annotations don't allow you to pass additional arguments and also happen
    // before validation
    public static function json_decode_to_array($value): array|null
    {
        if (empty($value)) {
            return null;
        }
        return json_decode($value, true);
    }

    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    #[Annotation\Filter(Callback::class, options: [
        'callback' => [self::class, 'json_decode_to_array']
    ])]
    #[Annotation\Validator(NotEmpty::class, options: [
        "messages" => [
            NotEmpty::IS_EMPTY  => "Please select an option"
        ]
    ])]
    public mixed $postoffice;

    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    public bool $selectPostoffice;

    #[Annotation\Validator(NotEmpty::class)]
    public string $location;
}
