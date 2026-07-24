<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Implementation\Writer;

use Closure;
use ReflectionProperty;
use ReflectionType;

/**
 * Base for the writers whose derivation depends on the property type - it
 * preloads the name and the declared type once, so every concrete writer
 * starts from the same pair instead of unpacking the reflection itself. A
 * writer that never looks at the type implements the interface directly.
 *
 * @internal
 */
abstract class AbstractTypeAwareWriter implements PrefixWriterInterface
{
    final public function write(ReflectionProperty $property, array $arguments): Closure
    {
        return $this->derive($property->getName(), $property->getType(), $arguments);
    }

    /**
     * @param array<int, mixed> $arguments
     *
     * @return Closure(object): void
     */
    abstract protected function derive(string $name, ?ReflectionType $type, array $arguments): Closure;
}
