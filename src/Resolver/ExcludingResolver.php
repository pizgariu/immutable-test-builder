<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Resolver;

use Closure;
use Pizgariu\ImmutableTestBuilder\Contract\PrefixResolverInterface;
use ReflectionProperty;

/**
 * excluding*() removes a value from a collection property, re-indexing what
 * remains, without replacing the whole collection.
 *
 * @internal
 */
final class ExcludingResolver implements PrefixResolverInterface
{
    public function write(ReflectionProperty $property, array $arguments): Closure
    {
        $name = $property->getName();
        $value = $arguments[0] ?? null;

        return static function (object $clone) use ($name, $value): void {
            /** @var array<int|string, mixed> $current */
            $current = $clone->{$name};
            $clone->{$name} = array_values(array_filter(
                $current,
                static fn (mixed $item): bool => $item !== $value,
            ));
        };
    }
}
