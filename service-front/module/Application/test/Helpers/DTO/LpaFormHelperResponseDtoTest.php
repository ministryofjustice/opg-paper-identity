<?php

declare(strict_types=1);

namespace ApplicationTest\Helpers\DTO;

use Application\Helpers\DTO\LpaFormHelperResponseDto;
use Laminas\Form\Annotation\AttributeBuilder;
use Laminas\Form\FormInterface;
use PHPUnit\Framework\TestCase;

class LpaFormHelperResponseDtoTest extends TestCase
{
    private LpaFormHelperResponseDto $lpaFormHelperResponseDto;

    private string $uuid;

    private FormInterface $form;

    private array $variables;

    public function setUp(): void
    {
        parent::setUp();

        $this->form = (new AttributeBuilder())->createForm(TestValidator::class);
        $this->uuid = "9130a21e-6e5e-4a30-8b27-76d21b747e60";

        $this->variables = [];

        $this->lpaFormHelperResponseDto = new LpaFormHelperResponseDto(
            $this->uuid,
            $this->form,
            $this->variables
        );
    }

    public function testGetUuid(): void
    {
        $this->assertEquals(
            $this->uuid,
            $this->lpaFormHelperResponseDto->getUuid()
        );
    }

    public function testGetForm(): void
    {
        $this->assertEquals(
            $this->form,
            $this->lpaFormHelperResponseDto->getForm()
        );
    }

    public function testGetVariables(): void
    {
        $this->assertEquals(
            $this->variables,
            $this->lpaFormHelperResponseDto->getVariables()
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
            $this->lpaFormHelperResponseDto->toArray()
        );
    }
}
