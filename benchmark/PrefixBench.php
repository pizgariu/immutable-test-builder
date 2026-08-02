<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Benchmark;

use PhpBench\Attributes as Bench;
use Pizgariu\ImmutableTestBuilder\Contract\Enum\Prefix;

/**
 * The grammar lookup every magic call passes through first.
 *
 * ofMethod() is a linear scan, so these subjects measure POSITION. Case order
 * carries no meaning for correctness - the literal boundary is what keeps with*
 * from swallowing without*, whatever the order - but it plainly carries a cost,
 * and the spread between the first case and the last is the price of the scan.
 * A miss pays for every case, which makes benchNoMatch the ceiling.
 *
 * Read the subjects against each other. The ratio survives another machine and
 * the durations do not, which is why nothing here asserts one.
 */
#[Bench\Revs(20000)]
#[Bench\Iterations(5)]
#[Bench\Warmup(1)]
#[Bench\RetryThreshold(3.0)]
final class PrefixBench
{
    public function benchFirstCase(): void
    {
        Prefix::ofMethod('fromManifest');
    }

    public function benchLastCase(): void
    {
        Prefix::ofMethod('withCallsign');
    }

    public function benchNoMatch(): void
    {
        Prefix::ofMethod('launchTowards');
    }

    public function benchLongestPrefix(): void
    {
        Prefix::ofMethod('includingDecal');
    }
}
