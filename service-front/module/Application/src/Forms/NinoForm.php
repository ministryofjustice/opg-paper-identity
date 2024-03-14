<?php

declare(strict_types=1);

namespace Application\Forms;

use Laminas\Form\Form;
use Application\Validators\NinoValidator;

class NinoForm extends Form
{
    public const FORM_NAME = 'ninoform';

    public function __construct()
    {
        parent::__construct(self::FORM_NAME);

        $this->add([
            'name' => 'nino',
            'type' => 'text',
            'attributes' => [
                'class' => 'govuk-input govuk-!-width-one-third'
            ]
        ]);
    }

    /**
     * @return array
     * @codeCoverageIgnore
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'nino'    => [
                'required'   => true,
                'filters'    => [

                ],
                'validators' => [
                    [
                        'name'                   => NinoValidator::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'messages' => [
//                                NotEmpty::IS_EMPTY => 'Enter an email address in the correct format,
//                                 like name@example.com',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
