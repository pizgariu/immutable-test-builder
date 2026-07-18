<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Enum;

/**
 * The modifier DSL as a single source of truth. The kernel's __call, the
 * bundled PHPStan reflection extension and every rule share this enum, so
 * the grammar can never drift between runtime and analysis.
 */
enum Prefix: string
{
    case From = 'from';
    case For = 'for';
    case As = 'as';
    case Having = 'having';
    case Including = 'including';
    case Excluding = 'excluding';
    case Without = 'without';
    case With = 'with';

    public static function ofMethod(string $methodName): ?self
    {
        foreach (self::cases() as $prefix) {
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

}
