<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Writer;

use Closure;
use Pizgariu\ImmutableTestBuilder\Contract\Writer\PrefixWriterInterface;
use ReflectionProperty;

/**
 * including*() appends to a collection property without replacing it.
 *
 * @internal
 */
final class IncludingWriter implements PrefixWriterInterface
{
    public function write(ReflectionProperty $property, array $arguments): Closure
    {
        $name = $property->getName();
        $value = $arguments[0] ?? null;

        return static function (object $clone) use ($name, $value): void {
            $clone->{$name}[] = $value;
        };
    }
}
