<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Contract\Enum;

/**
 * The modifier DSL as a single source of truth. The kernel's __call, the
 * bundled PHPStan reflection extension and every rule share this enum, so
 * the grammar can never drift between runtime and analysis.
 *
 * This is the formal grammar of the DSL and deliberately part of the stable
 * Contract surface. A consumer never has to call it, yet every builder is
 * written in these prefixes, so changing a case or its semantics is a
 * breaking change. Custom tooling over the DSL (generators, codemods, own
 * rules) may read it freely.
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
            if ($prefix->matches($methodName)) {
                return $prefix;
            }
        }

        return null;
    }

    /**
     * Whether the method name opens with this prefix followed by a capitalized
     * property name. The uppercase boundary keeps with* from swallowing
     * without* - the lowercase 'o' fails the match.
     */
    public function matches(string $methodName): bool
    {
        return 1 === preg_match(sprintf('/^%s[A-Z0-9]/', $this->value), $methodName);
    }

    /**
     * without* and as* name the entire change in the method name. Every
     * other prefix feeds the builder outside data. Exhaustive on purpose - a
     * new case must declare its appetite here or the analysis fails.
     */
    public function feeds(): bool
    {
        return match ($this) {
            self::Without, self::As => false,
            self::From, self::For, self::Having, self::Including, self::Excluding, self::With => true,
        };
    }

    /**
     * as* is the one prefix with an optional argument: asArmed() raises the
     * flag, asArmed(false) lowers it, asMothballed(null) clears a nullable one.
     */
    public function acceptsOptionalParameter(): bool
    {
        return self::As === $this;
    }

    /**
     * Projection of autoImplementable() in case order.
     *
     * @return list<self>
     */
    public static function magic(): array
    {
        return array_values(array_filter(
            self::cases(),
            static fn (self $prefix): bool => $prefix->autoImplementable(),
        ));
    }

    /**
     * Whether the kernel can implement this prefix from a property
     * declaration alone. from*, for* and having* are never magic - hydration,
     * ownership and multi-property concepts deserve a handwritten body.
     * Exhaustive on purpose - a new case must pick a side here.
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
        $base = lcfirst(substr($methodName, strlen($this->value)));

        return match ($this) {
            self::Including, self::Excluding => [$base, $base . 's'],
            default => [$base],
        };
    }
}
