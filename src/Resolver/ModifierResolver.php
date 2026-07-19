<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Resolver;

use BadMethodCallException;
use Closure;
use Pizgariu\ImmutableTestBuilder\Enum\Prefix;
use ReflectionClass;
use ReflectionNamedType;

/**
 * Turns a magic modifier call into the write it performs - the single source
 * of truth for what with*, without*, as*, including* and excluding* mean over
 * a builder's declared properties. AbstractBuilder::__call hands the result
 * straight to mutate(), so a magic call travels the same path as a handwritten
 * one. The returned closure is already bound into the concrete class scope, so
 * it writes sealed private state without unsealing it.
 *
 * @internal the kernel's derivation engine, not part of the public API
 */
final class ModifierResolver
{
    private function __construct() {}

    /**
     * @param class-string $class
     * @param array<int, mixed> $arguments
     *
     * @return Closure(object): void
     *
     * @throws BadMethodCallException when the name is outside the DSL, the prefix is never magic, no property matches or the arity is wrong
     */
    public static function resolve(string $class, string $method, array $arguments): Closure
    {
        $prefix = Prefix::ofMethod($method);

        if (null === $prefix) {
            throw new BadMethodCallException(sprintf(
                'Call to undefined method %s::%s() - no DSL prefix matches. The magic surface is %s over declared properties.',
                $class,
                $method,
                implode(', ', array_map(static fn (Prefix $magic): string => $magic->value . '*', Prefix::magic())),
            ));
        }

        if (!$prefix->autoImplementable()) {
            throw new BadMethodCallException(sprintf(
                '%s() on %s is a %s* modifier and %s* is never magic - hydration, ownership and multi-property concepts are written explicitly.',
                $method,
                $class,
                $prefix->value,
                $prefix->value,
            ));
        }

        $property = null;
        $reflection = new ReflectionClass($class);

        foreach ($prefix->propertyCandidates($method) as $candidate) {
            if (!$reflection->hasProperty($candidate)) {
                continue;
            }

            $candidateProperty = $reflection->getProperty($candidate);

            if (!$candidateProperty->isStatic()) {
                $property = $candidateProperty;

                break;
            }
        }

        if (null === $property) {
            throw new BadMethodCallException(sprintf(
                '%s() has no matching property on %s (tried $%s) - declare the property or write the modifier explicitly.',
                $method,
                $class,
                implode(', $', $prefix->propertyCandidates($method)),
            ));
        }

        $expectedArguments = $prefix->takesParameters() ? 1 : 0;

        if (count($arguments) !== $expectedArguments) {
            throw new BadMethodCallException(sprintf(
                '%s() on %s takes exactly %d argument(s), %d given - %s* modifiers have a fixed arity.',
                $method,
                $class,
                $expectedArguments,
                count($arguments),
                $prefix->value,
            ));
        }

        $name = $property->getName();
        $value = $arguments[0] ?? null;

        if (Prefix::As === $prefix) {
            $value = true;
        }

        if (Prefix::Without === $prefix) {
            $type = $property->getType();

            if (null === $type || $type->allowsNull()) {
                $value = null;
            } elseif ($type instanceof ReflectionNamedType) {
                $value = match ($type->getName()) {
                    'array' => [],
                    'string' => '',
                    'int' => 0,
                    'float' => 0.0,
                    'bool' => false,
                    default => throw new BadMethodCallException(sprintf(
                        'Cannot infer an empty value for $%s of type %s - write %s() explicitly.',
                        $name,
                        $type->getName(),
                        $method,
                    )),
                };
            } else {
                throw new BadMethodCallException(sprintf(
                    'Cannot infer an empty value for $%s - write %s() explicitly.',
                    $name,
                    $method,
                ));
            }
        }

        $write = match ($prefix) {
            Prefix::With, Prefix::Without, Prefix::As => static function (object $clone) use ($name, $value): void {
                $clone->{$name} = $value;
            },
            Prefix::Including => static function (object $clone) use ($name, $value): void {
                $clone->{$name}[] = $value;
            },
            Prefix::Excluding => static function (object $clone) use ($name, $value): void {
                /** @var array<int|string, mixed> $current */
                $current = $clone->{$name};
                $clone->{$name} = array_values(array_filter(
                    $current,
                    static fn (mixed $item): bool => $item !== $value,
                ));
            },
            Prefix::From, Prefix::For, Prefix::Having => throw new BadMethodCallException(sprintf(
                '%s() on %s is never magic.',
                $method,
                $class,
            )),
        };

        return Closure::bind($write, null, $class);
    }
}
