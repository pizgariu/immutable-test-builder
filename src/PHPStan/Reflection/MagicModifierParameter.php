<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\PHPStan\Reflection;

use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\Type;

/**
 * The single by-value parameter a magic modifier takes - required for the
 * feeding prefixes, optional with a default for as*.
 *
 * @internal
 */
final readonly class MagicModifierParameter implements ParameterReflection
{
    public function __construct(
        private string $name,
        private Type $type,
        private ?Type $defaultValue = null,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function isOptional(): bool
    {
        return null !== $this->defaultValue;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function passedByReference(): PassedByReference
    {
        return PassedByReference::createNo();
    }

    public function isVariadic(): bool
    {
        return false;
    }

    public function getDefaultValue(): ?Type
    {
        return $this->defaultValue;
    }
}
