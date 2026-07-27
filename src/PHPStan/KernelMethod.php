<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\PHPStan;

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
}
