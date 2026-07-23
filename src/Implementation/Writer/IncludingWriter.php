<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Implementation\Writer;

use BadMethodCallException;
use Closure;
use ReflectionNamedType;
use ReflectionType;

/**
 * including*() appends to a collection property without replacing it. The
 * property must be an array (a nullable one starts as an empty collection), so
 * it refuses loudly on any other type rather than crashing mid-append.
 *
 * @internal
 */
final class IncludingWriter extends AbstractTypeAwareWriter
{
    protected function derive(string $name, ?ReflectionType $type, array $arguments): Closure
    {
        if (!$type instanceof ReflectionNamedType || 'array' !== $type->getName()) {
            throw new BadMethodCallException(sprintf(
                'including*() targeting $%s appends to a collection but the property is %s - declare it array or write the modifier by hand.',
                $name,
                null === $type ? 'untyped' : (string) $type,
            ));
        }

        $value = $arguments[0] ?? null;

        return static function (object $clone) use ($name, $value): void {
            if (!is_array($clone->{$name})) {
                $clone->{$name} = [];
            }

            $clone->{$name}[] = $value;
        };
    }
}
