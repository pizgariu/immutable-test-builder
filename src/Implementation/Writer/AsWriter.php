<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Implementation\Writer;

use BadMethodCallException;
use Closure;
use Pizgariu\ImmutableTestBuilder\Contract\Writer\PrefixWriterInterface;
use ReflectionNamedType;
use ReflectionProperty;

/**
 * as*() sets a boolean flag - true by default, or an explicit bool when given,
 * or null when the property is nullable. The property must be a bool, so it
 * refuses loudly on any other type rather than writing a lie. asArmed() raises
 * the flag, asArmed(false) lowers it, asMothballed(null) clears a ?bool.
 *
 * @internal
 */
final class AsWriter implements PrefixWriterInterface
{
    public function write(ReflectionProperty $property, array $arguments): Closure
    {
        $name = $property->getName();
        $type = $property->getType();

        if (!$type instanceof ReflectionNamedType || 'bool' !== $type->getName()) {
            throw new BadMethodCallException(sprintf(
                'as%s() raises a boolean flag but $%s is %s - declare it bool or write the modifier by hand.',
                ucfirst($name),
                $name,
                null === $type ? 'untyped' : (string) $type,
            ));
        }

        $value = array_key_exists(0, $arguments) ? $arguments[0] : true;

        if (null === $value && !$type->allowsNull()) {
            throw new BadMethodCallException(sprintf(
                'as%s() was given null but $%s is not nullable - pass true or false, or make the property ?bool.',
                ucfirst($name),
                $name,
            ));
        }

        if (null !== $value && !is_bool($value)) {
            throw new BadMethodCallException(sprintf(
                'as%s() takes a bool%s but %s was given - as* sets a flag.',
                ucfirst($name),
                $type->allowsNull() ? ' or null' : '',
                get_debug_type($value),
            ));
        }

        return static function (object $clone) use ($name, $value): void {
            $clone->{$name} = $value;
        };
    }
}
