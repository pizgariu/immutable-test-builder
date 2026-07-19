<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Resolver;

use Closure;
use Pizgariu\ImmutableTestBuilder\Contract\PrefixResolverInterface;
use ReflectionProperty;

/**
 * as*() raises a boolean flag. It names the whole change, so it takes no
 * argument and always writes true. The opposite lowering is without*(), which
 * infers false for a bool.
 *
 * @internal
 */
final class AsResolver implements PrefixResolverInterface
{
    public function write(ReflectionProperty $property, array $arguments): Closure
    {
        $name = $property->getName();

        return static function (object $clone) use ($name): void {
            $clone->{$name} = true;
        };
    }
}
