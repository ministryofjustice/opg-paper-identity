<?php

declare(strict_types=1);

namespace ApplicationTest\Helpers\DTO;

use Application\Helpers\DTO\LpaHelperResponseDto;
use Laminas\Form\Annotation\AttributeBuilder;
use Laminas\Form\FormInterface;
use PHPUnit\Framework\TestCase;

class LpaHelperResponseDtoTest extends TestCase
{
    private LpaHelperResponseDto $lpaHelperResponseDto;

    private string $uuid;

    private FormInterface $form;

    private array $variables;

    public function setUp(): void
    {
        parent::setUp();

        $this->form = (new AttributeBuilder())->createForm(TestValidator::class);
        $this->uuid = "9130a21e-6e5e-4a30-8b27-76d21b747e60";

        $this->variables = [];

        $this->lpaHelperResponseDto = new LpaHelperResponseDto(
            $this->uuid,
            $this->form,
            $this->variables
        );
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(
            $this->uuid,
            $this->lpaHelperResponseDto->getUuid()
        );
    }

    public function testGetForm(): void
    {
        $this->assertEquals(
            $this->form,
            $this->lpaHelperResponseDto->getForm()
        );
    }

    public function testGetVariables(): void
    {
        $this->assertEquals(
            $this->variables,
            $this->lpaHelperResponseDto->getVariables()
        );
    }

    public function testToArray(): void
    {
        $this->assertEquals(
            [
                'uuid' => $this->uuid,
                'form' => $this->form,
                'variables' => [],
            ],
            $this->lpaHelperResponseDto->toArray()
        );
    }
}
