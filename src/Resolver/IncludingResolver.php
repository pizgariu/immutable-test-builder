<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Resolver;

use Closure;
use Pizgariu\ImmutableTestBuilder\Contract\PrefixResolverInterface;
use ReflectionProperty;

/**
 * including*() appends to a collection property without replacing it.
 *
 * @internal
 */
final class IncludingResolver implements PrefixResolverInterface
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
