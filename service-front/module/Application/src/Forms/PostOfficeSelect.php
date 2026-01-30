<?php

declare(strict_types=1);

namespace Application\Forms;

use GuzzleHttp\Promise\Is;
use Laminas\Form\Annotation;
use Laminas\Hydrator\ObjectPropertyHydrator;
use Laminas\Validator\NotEmpty;
use Laminas\Filter\Callback;

use function PHPUnit\Framework\isEmpty;
use function PHPUnit\Framework\isNan;

/**
 * @psalm-suppress MissingConstructor
 * @implements FormTemplate<array{
 *   postoffice: array{
 *     fad_code: string,
 *     address: string,
 *     post_code: string
 *   }|null,
 *   selectPostoffice: bool,
 *   searchString: string
 *  }>
 */
#[Annotation\Hydrator(ObjectPropertyHydrator::class)]
class PostOfficeSelect implements FormTemplate
{
    // annotations don't allow you to pass additional arguments and happen
    // before validation so need to wrap json_decode
    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function jsonDecodeToArray(?string $value): array|null
    {
        if (is_null($value) || $value === '') {
            return null;
        }
        return json_decode($value, true);
    }

    /**
     * @psalm-suppress PossiblyUnusedProperty
     * @var array{fad_code: string, address: string, post_code: string}
     */
    #[Annotation\Filter(Callback::class, options: [
        'callback' => [self::class, 'jsonDecodeToArray']
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

    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    public string $searchString;
}
