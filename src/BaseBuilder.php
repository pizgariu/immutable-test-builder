<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder;

use Closure;
use Faker\Generator;
use Pizgariu\ImmutableTestBuilder\Contract\Builder;

/**
 * Base class for immutable test data builders.
 *
 * A builder is created once with a perfect default: seed() fills every
 * ingredient with realistic faker data so build() succeeds with no further
 * calls. Each public modifier then returns a NEW instance via mutate(),
 * leaving the original builder untouched and reusable.
 *
 * The locale is fixed at creation time (createIn()) instead of being a
 * switchable modifier: switching locale mid-flight would leave the already
 * seeded values in the old locale, a whole class of stale-randomization
 * bugs this API makes unrepresentable.
 *
 * @template-covariant T
 * @implements Builder<T>
 */
abstract class BaseBuilder implements Builder
{
    final protected function __construct(private readonly string $locale)
    {
    }

    /**
     * Perfect default in the builder's default locale: the returned builder
     * must build() successfully with no further calls.
     */
    final public static function create(): static
    {
        return static::createIn(static::defaultLocale());
    }

    /**
     * The locale create() seeds from. Override in a project-wide base
     * builder to move every create() call site to another locale at once.
     */
    protected static function defaultLocale(): string
    {
        return Fakers::DEFAULT_LOCALE;
    }

    /**
     * Perfect default seeded from the given locale's generator. The locale
     * is fixed for the builder's whole lifetime; see the class-level
     * rationale.
     */
    final public static function createIn(string $locale): static
    {
        $builder = new static($locale);
        $builder->seed($builder->faker());

        return $builder;
    }

    /**
     * Fill the perfect default here; called exactly once by createIn().
     */
    abstract protected function seed(Generator $faker): void;

    /**
     * The memoized generator for this builder's locale.
     */
    final protected function faker(): Generator
    {
        return Fakers::locale($this->locale);
    }

    /**
     * The locale this builder was created in.
     */
    final public function locale(): string
    {
        return $this->locale;
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
}
