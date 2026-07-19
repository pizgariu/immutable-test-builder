<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Resolver;

use Closure;
use Pizgariu\ImmutableTestBuilder\Contract\PrefixResolverInterface;
use ReflectionProperty;

/**
 * with*() assigns the given argument to the property.
 *
 * @internal
 */
final class WithResolver implements PrefixResolverInterface
{
    public function write(ReflectionProperty $property, array $arguments): Closure
    {
        $name = $property->getName();
        $value = $arguments[0] ?? null;

        return static function (object $clone) use ($name, $value): void {
            $clone->{$name} = $value;
        };
    }
}
