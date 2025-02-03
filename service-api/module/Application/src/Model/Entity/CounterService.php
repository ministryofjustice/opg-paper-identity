<?php

declare(strict_types=1);

namespace Application\Model\Entity;

use Application\Validators\IsType;
use Laminas\Form\Annotation;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\Uuid;

class CounterService extends Entity
{
    /**
     * @var array{
     *   address: string,
     *   post_code: string,
     *   fad: string,
     * }
     */
    // #[Annotation\Required(false)]
    public array $selectedPostOffice;

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
     * @param properties-of<self> $data
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
     * @return properties-of<self>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
