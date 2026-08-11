<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

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

    /**
     * Generate a new random ContentNodeId.
     */
    public static function generate(): self
    {
        return new self(bin2hex(random_bytes(16)));
    }

    /**
     * Create a ContentNodeId from a string.
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
