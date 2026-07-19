<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Contract\Writer;

use BadMethodCallException;
use Closure;
use ReflectionProperty;

/**
 * One DSL prefix's write - the mutation it performs on a resolved property.
 * Each implementation owns a single prefix (assign, empty, append, filter).
 * ModifierResolver maps the parsed prefix to its writer and binds the result
 * into the concrete builder scope.
 *
 * @internal the kernel's derivation engine, not a consumer extension point
 */
interface PrefixWriterInterface
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
