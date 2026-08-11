<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

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

    /**
     * Generate a new random EntityId.
     */
    public static function generate(): self
    {
        return new self(bin2hex(random_bytes(16)));
    }

    /**
     * Create an EntityId from a string.
     */
    public static function fromString(string $value): self
    {
        return new self($value);
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
