<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Contract;

use BadMethodCallException;
use Closure;
use ReflectionProperty;

/**
 * One DSL prefix's magic behaviour - the write it performs on a resolved
 * property. Each implementation owns a single prefix (assign, empty, append,
 * filter). ModifierResolver maps the parsed prefix to its implementation and
 * binds the result into the concrete builder scope.
 *
 * @internal the kernel's derivation engine, not a consumer extension point
 */
interface PrefixResolverInterface
{
    /**
     * Derive the write this prefix performs. The property is already resolved
     * and the arity already checked. The returned closure is unbound - the
     * resolver binds it into the concrete class scope.
     *
     * @param array<int, mixed> $arguments
     *
     * @return Closure(object): void
     *
     * @throws BadMethodCallException when no value can be derived, e.g. without* on a non-nullable object property
     */
    public function write(ReflectionProperty $property, array $arguments): Closure;
}
