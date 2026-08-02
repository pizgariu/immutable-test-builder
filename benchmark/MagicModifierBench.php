<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Benchmark;

use PhpBench\Attributes as Bench;
use Pizgariu\ImmutableTestBuilder\Tests\Fixture\FreighterBuilder;
use Pizgariu\ImmutableTestBuilder\Tests\Fixture\SpaceshipBuilder;

/**
 * What the magic actually costs, stated as a ratio rather than a duration.
 *
 * A derived modifier resolves its prefix, reflects the target property and
 * binds a writer, where a declared one runs a closure the author already wrote.
 * Both end in the same mutate() clone, so the gap between these subjects IS the
 * price of not writing the method. That ratio holds on any machine. The numbers
 * beside it do not, which is why nothing here asserts a duration.
 */
#[Bench\Revs(5000)]
#[Bench\Iterations(5)]
#[Bench\Warmup(1)]
#[Bench\RetryThreshold(3.0)]
#[Bench\BeforeMethods('setUp')]
final class MagicModifierBench
{
    private FreighterBuilder $derived;

    private SpaceshipBuilder $declared;

    public function setUp(): void
    {
        $this->derived = FreighterBuilder::create();
        $this->declared = SpaceshipBuilder::create();
    }

    public function benchDerivedWith(): void
    {
        $this->derived->withCallsign('Nostromo');
    }

    public function benchDeclaredWith(): void
    {
        $this->declared->withName('Nostromo');
    }

    public function benchDerivedWithout(): void
    {
        $this->derived->withoutArmed();
    }

    public function benchDerivedAs(): void
    {
        $this->derived->asArmed();
    }

    public function benchDerivedIncluding(): void
    {
        $this->derived->includingDecal('flame');
    }

    public function benchDerivedExcluding(): void
    {
        $this->derived->excludingDecal('stripe');
    }
}
