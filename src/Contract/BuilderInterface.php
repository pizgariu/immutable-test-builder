<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Contract;

use Pizgariu\ImmutableTestBuilder\Exception\UnbuildableState;

/**
 * Contract for immutable test data builders.
 *
 * Perfect default: create() returns a builder that is complete from the very
 * first moment. Calling build() with no further setup succeeds and produces an
 * object seeded with realistic defaults. A test therefore states only the
 * values it asserts on. Everything else is already in place and valid, which
 * keeps tests short and stops them from over-specifying data they do not
 * care about.
 *
 * Immutability: every modifier returns a NEW builder instance and leaves the
 * receiver untouched. Two guarantees follow. Test isolation: a builder held in
 * a shared fixture, a class property or a helper method can never be corrupted
 * by one test on behalf of the next, because no call site can mutate it.
 * Safe reuse and branching: a partially tailored builder can serve as the
 * trunk for several divergent variants inside a single test, and none of the
 * variants observes the changes made to the others. Both guarantees hold for
 * scalar, array and immutable-object ingredients. A mutable object ingredient
 * must be replaced inside a modifier, never mutated in place, or deep-copied
 * in an overridden __clone().
 *
 * Modifier naming DSL (a documented contract of this library, statically
 * enforced by the bundled PHPStan rule set): with*(value) sets a value,
 * without*() empties or nullifies a value, as*() performs a semantic boolean
 * or state transition, from*(source) hydrates the builder from an existing
 * object, for*(owner) establishes context or ownership, including*(item) and
 * excluding*(item) extend or shrink a collection without replacing it, and
 * having*() atomically mutates the properties of one inseparable domain
 * concept. The prefixes set*, make* and add* are never used. Every modifier
 * returns a new instance.
 *
 * A builder driven into an impossible state never produces a broken object:
 * build() fails loudly with UnbuildableState, whose message names the builder
 * and tells the caller the way out.
 *
 * @template-covariant T
 */
interface BuilderInterface
{
    /**
     * Perfect default - the returned builder must build() successfully with no further calls.
     */
    public static function create(): static;

    /**
     * @return T
     *
     * @throws UnbuildableState when the builder is in a state that cannot produce a valid object
     */
    public function build(): mixed;
}
