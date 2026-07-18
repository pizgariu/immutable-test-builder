<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder;

/**
 * The modifier DSL as a single source of truth. The kernel's __call, the
 * bundled PHPStan reflection extension and every rule share this enum, so
 * the grammar can never drift between runtime and analysis.
 */
enum Prefix: string
{
    case With = 'with';
    case Without = 'without';
    case As = 'as';
    case From = 'from';
    case For = 'for';
    case Including = 'including';
    case Excluding = 'excluding';
    case Having = 'having';

    public static function ofMethod(string $methodName): ?self
    {
        foreach (self::matchOrder() as $prefix) {
            if (1 === preg_match(sprintf('/^%s[A-Z0-9]/', $prefix->value), $methodName)) {
                return $prefix;
            }
        }

        return null;
    }

    /**
     * The prefixes that name the entire change in the method name. Every
     * other prefix feeds the builder outside data.
     *
     * @return list<self>
     */
    public static function parameterless(): array
    {
        return [self::Without, self::As];
    }

    public function takesParameters(): bool
    {
        return !in_array($this, self::parameterless(), true);
    }

    /**
     * The prefixes the kernel can implement from a property declaration
     * alone. from*, for* and having* are never magic - hydration, ownership
     * and multi-property concepts deserve a handwritten body.
     *
     * @return list<self>
     */
    public static function magic(): array
    {
        return [self::With, self::Without, self::As, self::Including, self::Excluding];
    }

    public function autoImplementable(): bool
    {
        return in_array($this, self::magic(), true);
    }

    /**
     * Property names a magic call may resolve to. Collection prefixes speak
     * in singular (includingRole) about plural state ($roles), so they also
     * try the simple plural.
     *
     * @return list<string>
     */
    public function propertyCandidates(string $methodName): array
    {
        $base = lcfirst(substr($methodName, strlen($this->value)));

        return match ($this) {
            self::Including, self::Excluding => [$base, $base . 's'],
            default => [$base],
        };
    }

    /**
     * Longest prefixes first, so with never swallows without and for never
     * shadows anything it should not.
     *
     * @return list<self>
     */
    private static function matchOrder(): array
    {
        return [self::Including, self::Excluding, self::Without, self::Having, self::From, self::With, self::For, self::As];
    }
}
