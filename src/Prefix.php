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
        foreach (self::longestFirst() as $prefix) {
            if (1 === preg_match(sprintf('/^%s[A-Z0-9]/', $prefix->value), $methodName)) {
                return $prefix;
            }
        }

        return null;
    }

    public function rest(string $methodName): string
    {
        return substr($methodName, strlen($this->value));
    }

    /**
     * without* and as* name the entire change in the method name. Every
     * other prefix feeds the builder outside data.
     */
    public function takesParameters(): bool
    {
        return self::Without !== $this && self::As !== $this;
    }

    /**
     * The prefixes the kernel can implement from a property declaration
     * alone. from*, for* and having* are never magic - hydration, ownership
     * and multi-property concepts deserve a handwritten body.
     */
    public function autoImplementable(): bool
    {
        return match ($this) {
            self::With, self::Without, self::As, self::Including, self::Excluding => true,
            self::From, self::For, self::Having => false,
        };
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
        $base = lcfirst($this->rest($methodName));

        return match ($this) {
            self::Including, self::Excluding => [$base, $base . 's'],
            default => [$base],
        };
    }

    /**
     * @return list<self>
     */
    private static function longestFirst(): array
    {
        return [self::Including, self::Excluding, self::Without, self::Having, self::From, self::With, self::For, self::As];
    }
}
