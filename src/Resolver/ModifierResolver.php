<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Resolver;

use BadMethodCallException;
use Closure;
use Pizgariu\ImmutableTestBuilder\Enum\Prefix;
use ReflectionClass;

/**
 * Turns a magic modifier call into the write it performs. It parses the prefix,
 * maps it to the one resolver that owns its meaning (assign, empty, append,
 * filter), resolves the target property and checks the arity, then delegates
 * the write. AbstractBuilder::__call hands the result straight to mutate(), so a
 * magic call travels the same path as a handwritten one. The returned closure
 * is already bound into the concrete class scope, so it writes sealed private
 * state without unsealing it.
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

        $resolver = match ($prefix) {
            Prefix::With => new WithResolver(),
            Prefix::Without => new WithoutResolver(),
            Prefix::As => new AsResolver(),
            Prefix::Including => new IncludingResolver(),
            Prefix::Excluding => new ExcludingResolver(),
            Prefix::From, Prefix::For, Prefix::Having => throw new BadMethodCallException(sprintf(
                '%s() on %s is a %s* modifier and %s* is never magic - hydration, ownership and multi-property concepts are written explicitly.',
                $method,
                $class,
                $prefix->value,
                $prefix->value,
            )),
        };

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

        $expected = $prefix->takesParameters() ? 1 : 0;

        if (count($arguments) !== $expected) {
            throw new BadMethodCallException(sprintf(
                '%s() on %s takes exactly %d argument(s), %d given - %s* modifiers have a fixed arity.',
                $method,
                $class,
                $expected,
                count($arguments),
                $prefix->value,
            ));
        }

        return Closure::bind($resolver->write($property, $arguments), null, $class);
    }
}
