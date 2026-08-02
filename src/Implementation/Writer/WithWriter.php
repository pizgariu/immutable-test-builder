<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Implementation\Writer;

use Closure;
use ReflectionType;

/**
 * with*() assigns the given argument to the property. The one prefix that never
 * looks at the declared type, because an assignment does not have to pick a
 * value the way the emptying and collection prefixes do.
 *
 * @internal
 */
final class WithWriter implements PrefixWriterInterface
{
    public function write(string $name, ?ReflectionType $type, array $arguments): Closure
    {
        $value = $arguments[0] ?? null;

        return static function (object $clone) use ($name, $value): void {
            $clone->{$name} = $value;
        };
    }
}
