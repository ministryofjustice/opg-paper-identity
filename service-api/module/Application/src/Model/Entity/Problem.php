<?php

declare(strict_types=1);

namespace Application\Model\Entity;

use JsonSerializable;

/**
 * Serializable class to support errors in a RFC7807 format
 */
class Problem implements JsonSerializable
{
    public function __construct(
        public readonly string $title,
        public readonly string $type = '',
        public readonly int $status = 0,
        public readonly string $detail = '',
        public readonly array $extra = [],
    ) {
    }

    public function jsonSerialize(): array
    {
        $json = [
            'title' => $this->title,
            ...$this->extra,
        ];

        if ($this->type !== '') {
            $json['type'] = $this->type;
        }

        if ($this->status !== 0) {
            $json['status'] = $this->status;
        }

        if ($this->detail !== '') {
            $json['detail'] = $this->detail;
        }

        return $json;
    }
}
