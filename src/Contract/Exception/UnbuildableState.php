<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Contract\Exception;

use LogicException;

/**
 * Thrown by build() when a builder cannot produce a valid object.
 *
 * The message always names the offending builder by its short class name and
 * ends with concrete guidance, so the failing test points straight at the fix
 * instead of handing back a silently broken object.
 */
final class UnbuildableState extends LogicException
{
    public static function missing(string $builderClass, string $ingredient, string $advice): self
    {
        return new self(sprintf(
            '%s cannot build yet - missing %s. %s',
            self::shortName($builderClass),
            $ingredient,
            $advice,
        ));
    }

    public static function contradiction(string $builderClass, string $conflict, string $wayOut): self
    {
        return new self(sprintf(
            '%s was driven into a contradiction - %s. %s',
            self::shortName($builderClass),
            $conflict,
            $wayOut,
        ));
    }

    private static function shortName(string $builderClass): string
    {
        $lastSeparator = strrpos($builderClass, '\\');

        if ($lastSeparator === false) {
            return $builderClass;
        }

        return substr($builderClass, $lastSeparator + 1);
    }
}
