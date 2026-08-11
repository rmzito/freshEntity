<?php

declare(strict_types=1);

namespace Domain\ValueObject;

/**
 * Value Object representing a unique Entity identifier.
 * 
 * @label VERIFIED - Entity IDs are used throughout legacy code as string identifiers
 */
final class EntityId
{
    public function __construct(
        private readonly string $value
    ) {
        if ($value === '') {
            throw new \InvalidArgumentException('EntityId cannot be empty');
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
