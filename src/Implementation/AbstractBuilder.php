<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Implementation;

use BadMethodCallException;
use Closure;
use Pizgariu\ImmutableTestBuilder\Contract\BuilderInterface;
use Pizgariu\ImmutableTestBuilder\Resolver\ModifierResolver;

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
 * no method exists. from*, for* and having* are never magic. The derivation
 * itself lives in ModifierResolver.
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
    final protected function __construct() {}

    /**
     * Perfect default: the returned builder must build() successfully with no further calls.
     */
    final public static function create(): static
    {
        $builder = new static();
        $builder->seed();

        return $builder;
    }

    /**
     * The magic half of the DSL. ModifierResolver derives the write from the
     * property declarations and this funnels it through mutate() like every
     * handwritten modifier.
     *
     * @param array<int, mixed> $arguments
     *
     * @throws BadMethodCallException when the name is outside the DSL, the prefix is never magic, no property matches or the arity is wrong
     */
    final public function __call(string $method, array $arguments): static
    {
        return $this->mutate(ModifierResolver::resolve(static::class, $method, $arguments));
    }

    /**
     * The immutability engine: clones this builder, applies the mutation to
     * the clone and returns the clone. Every public modifier of a concrete
     * builder is a one-liner delegating here.
     *
     * The mutation is either a closure receiving the clone or a plain
     * property map - the dialect PHP 8.5's clone-with speaks, portable back
     * to 8.3 through a write bound into the concrete class scope. Once 8.5
     * becomes this package's floor the map form swaps its internals for the
     * native call and no call site moves.
     *
     * The clone is shallow: isolation holds for scalar, array and immutable
     * object ingredients. A mutable object ingredient is shared with the
     * clone - replace it inside the modifier instead of mutating it in
     * place, or deep-copy it in an overridden __clone().
     *
     * @param Closure(static): void|array<string, mixed> $mutation
     */
    final protected function mutate(Closure|array $mutation): static
    {
        $clone = clone $this;

        if ($mutation instanceof Closure) {
            $mutation($clone);

            return $clone;
        }

        $write = static function (object $target) use ($mutation): void {
            foreach ($mutation as $property => $value) {
                $target->{$property} = $value;
            }
        };

        Closure::bind($write, null, static::class)($clone);

        return $clone;
    }

    /**
     * Fill the perfect default here. Called exactly once by create().
     */
    abstract protected function seed(): void;
}
