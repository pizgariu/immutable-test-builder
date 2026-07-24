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
final class MagicProperty
{
    private function __construct() {}

    /**
     * @param ReflectionClass<object> $class
     */
    public static function resolve(ReflectionClass $class, Prefix $prefix, string $method): ?ReflectionProperty
    {
        foreach ($prefix->propertyCandidates($method) as $name) {
            if (!$class->hasProperty($name)) {
                continue;
            }

            $property = $class->getProperty($name);

            if (self::derivable($property)) {
                return $property;
            }
        }

        if (Prefix::Including === $prefix || Prefix::Excluding === $prefix) {
            $singular = lcfirst(substr($method, strlen($prefix->value)));

            foreach ($class->getProperties() as $property) {
                if (self::hasPlural($property, $singular) && self::derivable($property)) {
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
