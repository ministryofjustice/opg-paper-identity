<?php

declare(strict_types=1);

namespace Application\Model\Entity;

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
    #[Annotation\Validator(Uuid::class)]
    #[Annotation\Validator(NotEmpty::class)]
    public string $sessionId = '';

    #[Annotation\Required(false)]
    #[Annotation\Validator(Uuid::class)]
    #[Annotation\Validator(NotEmpty::class)]
    public string $notificationsAuthToken = '';

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
     *     sessionId: string,
     *     notificationsAuthToken: string
     * }
     */
    public function toArray(): array
    {
        return [
            'selectedPostOffice' => $this->selectedPostOffice,
            'selectedPostOfficeDeadline' => $this->selectedPostOfficeDeadline,
            'sessionId' => $this->sessionId,
            'notificationsAuthToken' => $this->notificationsAuthToken
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
