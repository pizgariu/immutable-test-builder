<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Resolver;

use BadMethodCallException;
use Closure;
use Pizgariu\ImmutableTestBuilder\Contract\PrefixResolverInterface;
use ReflectionNamedType;
use ReflectionProperty;

/**
 * without*() empties the property. The empty value is inferred from the
 * property type - null for a nullable, then the natural zero for array, string,
 * int, float and bool. A non-nullable object has no empty value the kernel can
 * guess, so it refuses and asks for an explicit modifier.
 *
 * @internal
 */
final class WithoutResolver implements PrefixResolverInterface
{
    public function write(ReflectionProperty $property, array $arguments): Closure
    {
        $name = $property->getName();
        $value = $this->emptyValueFor($property);

        return static function (object $clone) use ($name, $value): void {
            $clone->{$name} = $value;
        };
    }

    private function emptyValueFor(ReflectionProperty $property): mixed
    {
        $type = $property->getType();
        $name = $property->getName();

        if (null === $type || $type->allowsNull()) {
            return null;
        }

        if ($type instanceof ReflectionNamedType) {
            return match ($type->getName()) {
                'array' => [],
                'string' => '',
                'int' => 0,
                'float' => 0.0,
                'bool' => false,
                default => throw new BadMethodCallException(sprintf(
                    'Cannot infer an empty value for $%s of type %s - write without%s() explicitly.',
                    $name,
                    $type->getName(),
                    ucfirst($name),
                )),
            };
        }

        throw new BadMethodCallException(sprintf(
            'Cannot infer an empty value for $%s - write without%s() explicitly.',
            $name,
            ucfirst($name),
        ));
    }
}
