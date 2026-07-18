<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder;

use BadMethodCallException;
use Closure;
use Pizgariu\ImmutableTestBuilder\Contract\BuilderInterface;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;

/**
 * Base class for immutable test data builders.
 *
 * A builder is created once with a perfect default: seed() fills every
 * ingredient so build() succeeds with no further calls. Each public modifier
 * then returns a NEW instance via mutate(), leaving the original builder
 * untouched and reusable.
 *
 * Trivial modifiers do not exist as code. __call implements with*, without*,
 * as*, including* and excluding* straight from the property declarations,
 * writing sealed private state through a closure bound into the concrete
 * class scope. A declared method always wins - the engine only answers when
 * no method exists. from*, for* and having* are never magic.
 *
 * The kernel imposes no randomness strategy: seed() is plain PHP. A project
 * that wants randomized defaults calls its own generator (faker or anything
 * else) inside seed() - the kernel itself stays dependency-free.
 *
 * @template-covariant T
 * @implements BuilderInterface<T>
 */
abstract class AbstractBuilder implements BuilderInterface
{
    final protected function __construct()
    {
    }

    /**
     * Perfect default: the returned builder must build() successfully with
     * no further calls.
     */
    final public static function create(): static
    {
        $builder = new static();
        $builder->seed();

        return $builder;
    }

    /**
     * Fill the perfect default here. Called exactly once by create().
     */
    abstract protected function seed(): void;

    /**
     * The magic half of the DSL. Resolves the prefix, the target property
     * and the written value, then funnels the change through mutate() like
     * every handwritten modifier.
     *
     * @param array<int, mixed> $arguments
     *
     * @throws BadMethodCallException when the name is outside the DSL, the
     *                                prefix is never magic, no property
     *                                matches or the arity is wrong
     */
    final public function __call(string $method, array $arguments): static
    {
        $prefix = Prefix::ofMethod($method);

        if (null === $prefix) {
            throw new BadMethodCallException(sprintf(
                'Call to undefined method %s::%s() - no DSL prefix matches. The magic surface is with*, without*, as*, including* and excluding* over declared properties.',
                static::class,
                $method,
            ));
        }

        if (in_array($prefix, [Prefix::From, Prefix::For, Prefix::Having], true)) {
            throw new BadMethodCallException(sprintf(
                '%s() on %s is a %s* modifier and %s* is never magic - hydration, ownership and multi-property concepts are written explicitly.',
                $method,
                static::class,
                $prefix->value,
                $prefix->value,
            ));
        }

        $property = $this->magicProperty($prefix, $method);
        $expectedArguments = $prefix->takesParameters() ? 1 : 0;

        if (count($arguments) !== $expectedArguments) {
            throw new BadMethodCallException(sprintf(
                '%s() on %s takes exactly %d argument(s), %d given - %s* modifiers have a fixed arity.',
                $method,
                static::class,
                $expectedArguments,
                count($arguments),
                $prefix->value,
            ));
        }

        $name = $property->getName();
        $value = match ($prefix) {
            Prefix::Without => self::emptyValueFor($property, $method),
            Prefix::As => true,
            default => $arguments[0] ?? null,
        };

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
                static::class,
            )),
        };

        return $this->mutate(Closure::bind($write, null, static::class));
    }

    /**
     * The immutability engine: clones this builder, invokes the mutation
     * with the clone and returns the clone. Every public modifier of a
     * concrete builder is a one-liner delegating here.
     *
     * The clone is shallow: isolation holds for scalar, array and immutable
     * object ingredients. A mutable object ingredient is shared with the
     * clone - replace it inside the modifier instead of mutating it in
     * place, or deep-copy it in an overridden __clone().
     *
     * @param Closure(static): void $mutation
     */
    final protected function mutate(Closure $mutation): static
    {
        $clone = clone $this;
        $mutation($clone);

        return $clone;
    }

    private function magicProperty(Prefix $prefix, string $method): ReflectionProperty
    {
        $reflection = new ReflectionClass(static::class);

        foreach ($prefix->propertyCandidates($method) as $candidate) {
            if (!$reflection->hasProperty($candidate)) {
                continue;
            }

            $property = $reflection->getProperty($candidate);

            if (!$property->isStatic()) {
                return $property;
            }
        }

        throw new BadMethodCallException(sprintf(
            '%s() has no matching property on %s (tried $%s) - declare the property or write the modifier explicitly.',
            $method,
            static::class,
            implode(', $', $prefix->propertyCandidates($method)),
        ));
    }

    private static function emptyValueFor(ReflectionProperty $property, string $method): mixed
    {
        $type = $property->getType();

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
                    'Cannot infer an empty value for $%s of type %s - write %s() explicitly.',
                    $property->getName(),
                    $type->getName(),
                    $method,
                )),
            };
        }

        throw new BadMethodCallException(sprintf(
            'Cannot infer an empty value for $%s - write %s() explicitly.',
            $property->getName(),
            $method,
        ));
    }
}
