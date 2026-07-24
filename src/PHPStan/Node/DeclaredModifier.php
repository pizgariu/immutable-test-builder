<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\PHPStan\Node;

use PhpParser\Node\Stmt\ClassMethod;
use Pizgariu\ImmutableTestBuilder\Contract\Enum\Prefix;

/**
 * Which declared method counts as a modifier - public, non-static and opening
 * with a DSL prefix. The one place the rules answer that question about a
 * method node.
 */
final class DeclaredModifier
{
    private function __construct() {}

    public static function name(ClassMethod $method): ?string
    {
        if (!$method->isPublic() || $method->isStatic()) {
            return null;
        }

        $name = $method->name->toString();

        return null === Prefix::ofMethod($name) ? null : $name;
    }
}
