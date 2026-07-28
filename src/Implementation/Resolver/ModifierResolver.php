<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Implementation\Resolver;

use BadMethodCallException;
use Closure;
use Pizgariu\ImmutableTestBuilder\Contract\Enum\Prefix;
use Pizgariu\ImmutableTestBuilder\Implementation\Writer\AsWriter;
use Pizgariu\ImmutableTestBuilder\Implementation\Writer\ExcludingWriter;
use Pizgariu\ImmutableTestBuilder\Implementation\Writer\IncludingWriter;
use Pizgariu\ImmutableTestBuilder\Implementation\Writer\WithoutWriter;
use Pizgariu\ImmutableTestBuilder\Implementation\Writer\WithWriter;
use ReflectionClass;
use ReflectionException;

/**
 * Turns a magic modifier call into the write it performs. It parses the prefix,
 * maps it to the one writer that owns its meaning (assign, empty, append,
 * filter), resolves the target property and checks the arity, then delegates
 * the write. AbstractBuilder::__call hands the result straight to mutate(), so a
 * magic call travels the same path as a handwritten one. The returned closure
 * is already bound into the concrete class scope, so it writes sealed private
 * state without unsealing it.
 *
 * @internal the kernel's derivation engine, not part of the public API
 */
final class ModifierResolver
{
    private function __construct() {}

    /**
     * @param class-string $class
     * @param array<int|string, mixed> $arguments
     *
     * @return Closure(object): void
     *
     * @throws BadMethodCallException when the name is outside the DSL, the prefix is never magic, no property matches or the arity is wrong
     */
    public static function resolve(string $class, string $method, array $arguments): Closure
    {
        $prefix = Prefix::ofMethod($method);

        if (null === $prefix) {
            throw new BadMethodCallException(sprintf(
                'Call to undefined method %s::%s() - no DSL prefix matches. The magic surface is %s over declared properties.',
                $class,
                $method,
                implode(', ', array_map(static fn (Prefix $magic): string => $magic->value . '*', Prefix::magic())),
            ));
        }

        $writer = match ($prefix) {
            Prefix::As => new AsWriter(),
            Prefix::Including => new IncludingWriter(),
            Prefix::Excluding => new ExcludingWriter(),
            Prefix::Without => new WithoutWriter(),
            Prefix::With => new WithWriter(),
            Prefix::From, Prefix::For, Prefix::Having => throw new BadMethodCallException(sprintf(
                '%s() on %s is a %s* modifier and %s* is never magic - hydration, ownership and multi-property concepts are written explicitly.',
                $method,
                $class,
                $prefix->value,
                $prefix->value,
            )),
        };

        if (!array_is_list($arguments)) {
            throw new BadMethodCallException(sprintf(
                '%s() on %s does not accept named arguments - a magic modifier reads its value positionally, so call %s(value).',
                $method,
                $class,
                $method,
            ));
        }

        try {
            $reflection = new ReflectionClass($class);
            // @phpstan-ignore catch.neverThrown (class-string is a phpdoc contract, not a runtime guarantee - the belt stays for callers that break it)
        } catch (ReflectionException $exception) {
            throw new BadMethodCallException(sprintf(
                'Cannot resolve %s() - reflection failed on %s, the very class executing this call. That state should be impossible, so if it surfaces the runtime or autoloader is broken, not the builder. Original error: %s',
                $method,
                $class,
                $exception->getMessage(),
            ), 0, $exception);
        }

        $property = PropertyResolver::resolve($reflection, $prefix, $method);

        if (null === $property) {
            throw new BadMethodCallException(sprintf(
                '%s() has no matching property on %s (tried $%s) - declare the property, write the modifier explicitly, or drop a #[NotMagic] attribute.',
                $method,
                $class,
                implode(', $', $prefix->propertyCandidates($method)),
            ));
        }

        if ($prefix->acceptsOptionalParameter()) {
            if (count($arguments) > 1) {
                throw new BadMethodCallException(sprintf(
                    '%s() on %s takes at most 1 argument, %d given - as* raises a flag, optionally to an explicit bool.',
                    $method,
                    $class,
                    count($arguments),
                ));
            }
        } else {
            $expected = $prefix->feeds() ? 1 : 0;

            if (count($arguments) !== $expected) {
                throw new BadMethodCallException(sprintf(
                    '%s() on %s takes exactly %d argument(s), %d given - %s* modifiers have a fixed arity.',
                    $method,
                    $class,
                    $expected,
                    count($arguments),
                    $prefix->value,
                ));
            }
        }

        return Closure::bind($writer->write($property, $arguments), null, $class);
    }
}
