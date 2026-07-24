<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Implementation;

use BadMethodCallException;
use Closure;
use Pizgariu\ImmutableTestBuilder\Contract\BuilderInterface;
use Pizgariu\ImmutableTestBuilder\Implementation\Resolver\ModifierResolver;

/**
 * Base class for immutable test data builders.
 *
 * A builder is created once with a perfect default - seed() fills every
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
 * Randomness is deliberately the consumer's call, not the kernel's. seed() is
 * plain PHP, and plain PHP already admits every strategy a kernel could bake
 * in - a fixed constant, a random_int() suffix, faker or a project generator
 * are all reachable inside seed() with nothing to configure. Any strategy the
 * kernel imposed would only narrow that and pull in a dependency, so it
 * imposes none and stays dependency-free. A generator shared across builders
 * belongs on a project-owned abstract base, which the rules exempt.
 *
 * @template-covariant T
 * @implements BuilderInterface<T>
 */
abstract class AbstractBuilder implements BuilderInterface
{
    final protected function __construct() {}

    /**
     * Perfect default - the returned builder must build() successfully with no further calls.
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
     * @param array<int|string, mixed> $arguments
     *
     * @throws BadMethodCallException when the name is outside the DSL, the prefix is never magic, no property matches, the arity is wrong or the arguments are named
     */
    final public function __call(string $method, array $arguments): static
    {
        return $this->mutate(ModifierResolver::resolve(static::class, $method, $arguments));
    }

    /**
     * The immutability engine - clones this builder, applies the mutation to
     * the clone and returns the clone. Every public modifier of a concrete
     * builder is a one-liner delegating here.
     *
     * The mutation is either a closure receiving the clone or a plain
     * property map - the dialect PHP 8.5's clone-with speaks, portable back
     * to 8.3 through a write bound into the concrete class scope. Once 8.5
     * becomes this package's floor the map form swaps its internals for the
     * native call and no call site moves.
     *
     * The clone is shallow - isolation holds for scalar, array and immutable
     * object ingredients. A mutable object ingredient is shared with the
     * clone - replace it inside the modifier instead of mutating it in
     * place, or deep-copy it in an overridden __clone().
     *
     * The map form refuses loudly when a key names no property the concrete
     * scope can write - a typo or a parent-private ingredient would otherwise
     * become a silent dynamic property and build() would return stale data.
     *
     * @param Closure(static): void|array<string, mixed> $mutation
     *
     * @throws BadMethodCallException when a map key names no property visible to the concrete builder
     */
    final protected function mutate(Closure|array $mutation): static
    {
        if (is_array($mutation)) {
            foreach (array_keys($mutation) as $property) {
                if (!property_exists(static::class, $property)) {
                    throw new BadMethodCallException(sprintf(
                        'mutate() on %s cannot write $%s - the concrete scope sees no such property. Fix the key, or declare shared base state protected so the bound write can reach it.',
                        static::class,
                        $property,
                    ));
                }
            }
        }

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
