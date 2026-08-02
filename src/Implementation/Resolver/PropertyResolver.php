<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Implementation\Resolver;

use Pizgariu\ImmutableTestBuilder\Contract\Attribute\NotMagic;
use Pizgariu\ImmutableTestBuilder\Contract\Attribute\Plural;
use Pizgariu\ImmutableTestBuilder\Contract\Enum\Prefix;
use ReflectionClass;
use ReflectionProperty;

/**
 * Resolves which declared property a magic call targets - the one place that
 * answer lives, so the runtime resolver and the PHPStan extension can never
 * disagree. It reads the same reflection both sides already hold, honours
 * #[NotMagic] (sealed properties are invisible to derivation) and #[Plural]
 * (an irregular collection plural), and returns the first name match without
 * looking at the type - the writer refuses a wrong type at runtime and the
 * extension refuses to advertise it, each in its own layer.
 *
 * @internal the kernel's derivation engine, not part of the public API
 */
final class PropertyResolver
{
    private function __construct() {}

    /**
     * @param ReflectionClass<object> $class
     */
    public static function resolve(ReflectionClass $class, Prefix $prefix, string $method): ?ReflectionProperty
    {
        // Memoised per class and method, the two things the answer depends on.
        // A miss is cached as null just like a hit, so a refusal costs reflection
        // once as well. The class belongs in the key because the same method name
        // reaches a different property on every builder that declares it.
        /** @var array<string, ReflectionProperty|null> $resolved */
        static $resolved = [];

        $key = $class->getName() . '::' . $method;

        if (array_key_exists($key, $resolved)) {
            return $resolved[$key];
        }

        return $resolved[$key] = self::derive($class, $prefix, $method);
    }

    /**
     * @param ReflectionClass<object> $class
     */
    private static function derive(ReflectionClass $class, Prefix $prefix, string $method): ?ReflectionProperty
    {
        $candidates = $prefix->propertyCandidates($method);

        foreach ($candidates as $name) {
            if (!$class->hasProperty($name)) {
                continue;
            }

            $property = $class->getProperty($name);

            if (self::derivable($property)) {
                return $property;
            }
        }

        if (Prefix::Including === $prefix || Prefix::Excluding === $prefix) {
            // The singular the method spoke, taken from the candidates rather
            // than sliced off the name again, so the Prefix enum stays the only
            // place that knows how a method name becomes a property name.
            $singular = $candidates[0];

            foreach ($class->getProperties() as $property) {
                if (self::derivable($property) && self::hasPlural($property, $singular)) {
                    return $property;
                }
            }
        }

        return null;
    }

    private static function derivable(ReflectionProperty $property): bool
    {
        return !$property->isStatic() && [] === $property->getAttributes(NotMagic::class);
    }

    private static function hasPlural(ReflectionProperty $property, string $singular): bool
    {
        foreach ($property->getAttributes(Plural::class) as $attribute) {
            if ($singular === $attribute->newInstance()->of) {
                return true;
            }
        }

        return false;
    }
}
