<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder;

use Closure;
use Pizgariu\ImmutableTestBuilder\Contract\BuilderInterface;

/**
 * Base class for immutable test data builders.
 *
 * A builder is created once with a perfect default: seed() fills every
 * ingredient so build() succeeds with no further calls. Each public modifier
 * then returns a NEW instance via mutate(), leaving the original builder
 * untouched and reusable.
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
}
