<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Implementation\Writer;

use BadMethodCallException;
use Closure;
use Pizgariu\ImmutableTestBuilder\Contract\Writer\PrefixWriterInterface;
use ReflectionNamedType;
use ReflectionProperty;

/**
 * as*() raises a boolean flag. It names the whole change, so it takes no
 * argument and always writes true. The opposite lowering is without*(), which
 * infers false for a bool. The property must be a bool - raising a flag on any
 * other type would write a lie, so the writer refuses loudly instead.
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

        return static function (object $clone) use ($name): void {
            $clone->{$name} = true;
        };
    }
}
