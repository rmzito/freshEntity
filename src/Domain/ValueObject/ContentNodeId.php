<?php

declare(strict_types=1);

namespace Domain\ValueObject;

/**
 * Value Object representing a unique ContentNode identifier.
 * 
 * @label VERIFIED - ContentNode IDs are used throughout legacy code
 */
final class ContentNodeId
{
    public function __construct(
        private readonly string $value
    ) {
        if ($value === '') {
            throw new \InvalidArgumentException('ContentNodeId cannot be empty');
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
