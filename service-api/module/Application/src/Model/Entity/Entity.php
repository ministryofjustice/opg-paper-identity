<?php

declare(strict_types=1);

namespace Application\Model\Entity;

use ArrayIterator;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * @implements IteratorAggregate<string, mixed>
 */
abstract class Entity implements IteratorAggregate, JsonSerializable
{
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->jsonSerialize());
    }
}
