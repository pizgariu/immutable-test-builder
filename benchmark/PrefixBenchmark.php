<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Benchmark;

use PhpBench\Attributes as Benchmark;
use Pizgariu\ImmutableTestBuilder\Contract\Enum\Prefix;

/**
 * The grammar lookup every magic call passes through first.
 *
 * The claim under measurement is that the cost is FLAT across the DSL. Nothing
 * is scanned, since the boundary is read off the name and the value looked up,
 * so a prefix declared last costs what one declared first costs and a name from
 * outside the DSL costs the same again rather than paying for every case before
 * giving up. The only thing left that can move a reading is how many characters
 * the prefix has, which is why the longest and the shortest both stand here.
 *
 * Read the subjects against each other. Their spread is the whole claim, and a
 * duration would not survive another machine, which is why none is asserted.
 */
#[Benchmark\Revs(20000)]
#[Benchmark\Iterations(5)]
#[Benchmark\Warmup(1)]
#[Benchmark\RetryThreshold(3.0)]
final class PrefixBenchmark
{
    public function benchShortestPrefix(): void
    {
        Prefix::ofMethod('asArmed');
    }

    public function benchTypicalPrefix(): void
    {
        Prefix::ofMethod('withCallsign');
    }

    public function benchLongestPrefix(): void
    {
        Prefix::ofMethod('includingDecal');
    }

    public function benchFirstDeclaredPrefix(): void
    {
        Prefix::ofMethod('fromManifest');
    }

    public function benchNoMatch(): void
    {
        Prefix::ofMethod('launchTowards');
    }
}
