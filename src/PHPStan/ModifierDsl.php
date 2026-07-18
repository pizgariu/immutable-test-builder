<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\PHPStan;

/**
 * The modifier naming DSL as a single testable definition, shared by the
 * rules that enforce it.
 */
final class ModifierDsl
{
    public const string PATTERN = '/^(?:with|without|as|from|for|including|excluding|having)[A-Z0-9]/';

    private function __construct()
    {
    }

    public static function matches(string $methodName): bool
    {
        return 1 === preg_match(self::PATTERN, $methodName);
    }
}
