<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Implementation\Writer;

use BadMethodCallException;
use Closure;
use ReflectionNamedType;
use ReflectionType;

/**
 * excluding*() removes a value from a collection property, re-indexing what
 * remains, without replacing the whole collection. The property must be an
 * array (a nullable one holding null has nothing to exclude and stays null),
 * so it refuses loudly on any other type rather than crashing mid-filter.
 *
 * @internal
 */
final class ExcludingWriter implements PrefixWriterInterface
{
    public function write(string $name, ?ReflectionType $type, array $arguments): Closure
    {
        if (!$type instanceof ReflectionNamedType || 'array' !== $type->getName()) {
            throw new BadMethodCallException(sprintf(
                'excluding*() targeting $%s filters a collection but the property is %s - declare it array or write the modifier by hand.',
                $name,
                null === $type ? 'untyped' : (string) $type,
            ));
        }

        $value = $arguments[0] ?? null;

        return static function (object $clone) use ($name, $value): void {
            $current = $clone->{$name};

            if (!is_array($current)) {
                return;
            }

            $clone->{$name} = array_values(array_filter(
                $current,
                static fn (mixed $item): bool => $item !== $value,
            ));
        };
    }
}
