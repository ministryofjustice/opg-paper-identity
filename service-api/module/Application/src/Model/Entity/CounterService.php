<?php

declare(strict_types=1);

namespace Application\Model\Entity;

use Application\Validators\IsType;
use JsonSerializable;
use Laminas\Form\Annotation;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\Uuid;

class CounterService implements JsonSerializable
{
    #[Annotation\Required(false)]
    public string $selectedPostOffice = '';
    #[Annotation\Required(false)]
    public string $selectedPostOfficeDeadline = '';

    #[Annotation\Required(false)]
    public string $notificationState = '';

    #[Annotation\Required(false)]
    #[Annotation\Validator(Uuid::class)]
    #[Annotation\Validator(NotEmpty::class)]
    public string $notificationsAuthToken = '';

    #[Annotation\Required(false)]
    public string $state = '';

    #[Annotation\Required(false)]
    #[Annotation\Validator(IsType::class, options: ['type' => 'boolean'])]
    #[Annotation\Validator(NotEmpty::class, options: [NotEmpty::NULL])]
    public bool $result = false;

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(mixed $data): self
    {
        $instance = new self();

        foreach ($data as $key => $value) {
            if (! property_exists($instance, $key)) {
                throw new \Exception(sprintf('%s does not have property "%s"', $instance::class, $key));
            }

            $instance->{$key} = $value;
        }

        return $instance;
    }

    /**
     * @returns array{
     *     selectedPostOffice: string,
     *     selectedPostOfficeDeadline: string,
     *     notificationsAuthToken: string,
     *     notificationState: string,
     *     state: string,
     *     result: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'selectedPostOffice' => $this->selectedPostOffice,
            'selectedPostOfficeDeadline' => $this->selectedPostOfficeDeadline,
            'notificationsAuthToken' => $this->notificationsAuthToken,
            'notificationState' => $this->notificationState,
            'state' => $this->state,
            'result' => $this->result
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
