<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\PHPStan;

use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;

/**
 * The kernel's own method names as one vocabulary - the rules compare against these instead of scattering string literals.
 *
 * @internal
 */
enum KernelMethod: string
{
    case Create = 'create';
    case Seed = 'seed';
    case Mutate = 'mutate';
    case Build = 'build';

    /**
     * Whether a name means this method, given either the parser node carrying it
     * or a string a caller already resolved.
     *
     * Taking the node is what earns this its place. A call's name is only an
     * Identifier when it is written out, so every rule comparing one had to guard
     * for that first, and the guard was repeated wherever the comparison was. It
     * lives here now. A computed name arrives as an Expr and falls out of the
     * identity comparison on type alone - false, which is the only honest answer
     * for a name that could be anything at runtime.
     */
    public function matches(Expr|Identifier|string $name): bool
    {
        return $this->value === ($name instanceof Identifier ? $name->toString() : $name);
    }
}
