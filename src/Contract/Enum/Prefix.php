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
    // Declared in reading order, from the prefixes that bring outside data to
    // the ones that name the change themselves. Nothing derives meaning or cost
    // from the position of a case, so this order is free to be the one that
    // reads best.
    case From = 'from';
    case For = 'for';
    case As = 'as';
    case Having = 'having';
    case Including = 'including';
    case Excluding = 'excluding';
    case Without = 'without';
    case With = 'with';

    /**
     * The characters a property name may open with, right after the prefix.
     * Held as a literal so the boundary test never depends on the locale the
     * way a ctype call would.
     */
    private const string PROPERTY_START = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    /**
     * The prefix a method name opens with, or null when the name is outside the
     * DSL entirely.
     *
     * The name states where its own prefix ends, at the lowercase run before a
     * property name begins, so this reads that boundary once and looks the value
     * up. No case is asked whether it matches and none is walked past, which is
     * what keeps the declaration order above free of any weight.
     *
     * Deliberately no regular expression either. This runs on every magic call,
     * and a boundary scan plus a lookup costs a fraction of compiling a pattern.
     */
    public static function ofMethod(string $methodName): ?self
    {
        $length = strcspn($methodName, self::PROPERTY_START);

        if ($length === strlen($methodName)) {
            return null;
        }

        return self::tryFrom(substr($methodName, 0, $length));
    }

    /**
     * Whether this is the prefix the method name opens with. Answered by
     * ofMethod() rather than by its own comparison, so the grammar is parsed in
     * exactly one place and the two can never drift apart.
     *
     * The uppercase or digit boundary is what keeps with* from swallowing
     * without*, because the lowercase 'o' of withoutFuel is not where a property
     * name starts, so the whole word is read as the prefix instead.
     */
    public function matches(string $methodName): bool
    {
        return $this === self::ofMethod($methodName);
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
     * as* is the one prefix with an optional argument - asArmed() raises the
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
            self::As, self::Including, self::Excluding, self::Without, self::With => true,
            self::From, self::For, self::Having => false,
        };
    }

    /**
     * Property names a magic call may resolve to. Collection prefixes speak
     * in singular (includingRole) about plural state ($roles), so they also
     * try the simple plural. Exhaustive on purpose, like the two above - a new
     * case must say whether it speaks plural instead of inheriting a default.
     *
     * @return list<string>
     */
    public function propertyCandidates(string $methodName): array
    {
        $base = lcfirst(substr($methodName, strlen($this->value)));

        return match ($this) {
            self::Including, self::Excluding => [$base, $base . 's'],
            self::From, self::For, self::As, self::Having, self::Without, self::With => [$base],
        };
    }
}
