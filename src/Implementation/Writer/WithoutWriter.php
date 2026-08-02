<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Implementation\Writer;

use BadMethodCallException;
use Closure;
use ReflectionNamedType;
use ReflectionType;

/**
 * without*() empties the property. The empty value is inferred from the
 * property type - null for a nullable, then the natural zero for array, string,
 * int, float and bool. A non-nullable object has no empty value the kernel can
 * guess, so it refuses and asks for an explicit modifier.
 *
 * @internal
 */
final class WithoutWriter implements PrefixWriterInterface
{
    public function write(string $name, ?ReflectionType $type, array $arguments): Closure
    {
        $value = $this->emptyValueFor($name, $type);

        return static function (object $clone) use ($name, $value): void {
            $clone->{$name} = $value;
        };
    }

    private function emptyValueFor(string $name, ?ReflectionType $type): mixed
    {
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
