<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Benchmark;

use PhpBench\Attributes as Benchmark;
use Pizgariu\ImmutableTestBuilder\Tests\Fixture\ColonyBuilder;

/**
 * What the DSL costs at the size a real builder actually is.
 *
 * The other subjects price single operations on small fixtures, which answers
 * whether the kernel is fast and not whether the answer matters. This one runs
 * twenty ingredients and chains of ten, so the readings can be compared against
 * the one number that decides it - what create() costs once seed() has run.
 *
 * Two claims live here. Deriving a chain costs a small multiple of declaring it,
 * NOT a growing one, because the derivation is memoised per class and method, so
 * the tenth call in a chain pays what the second one paid. And whatever that
 * multiple is, it is measured against create(), which is where a project's own
 * seed() work lands and where a suite's time is actually spent.
 */
#[Benchmark\Revs(5000)]
#[Benchmark\Iterations(5)]
#[Benchmark\Warmup(1)]
#[Benchmark\RetryThreshold(3.0)]
#[Benchmark\BeforeMethods('setUp')]
final class RealisticBuilderBenchmark
{
    private ColonyBuilder $colony;

    public function setUp(): void
    {
        $this->colony = ColonyBuilder::create();
    }

    public function benchCreateTwentyIngredients(): void
    {
        ColonyBuilder::create();
    }

    public function benchOneDerived(): void
    {
        $this->colony->withDesignation('Hadley');
    }

    public function benchOneDeclared(): void
    {
        $this->colony->withRegistry('HH-0001');
    }

    public function benchChainOfTenDerived(): void
    {
        $this->colony
            ->withDesignation('Hadley')
            ->withSector('LV-426')
            ->withProcessors(2)
            ->withAtmosphere(0.7)
            ->withOperator('Weyland-Yutani')
            ->asTerraformed()
            ->asQuarantined(false)
            ->withoutOperator()
            ->includingModule('atmosphere')
            ->excludingModule('operations')
        ;
    }

    public function benchChainOfTenDeclared(): void
    {
        $this->colony
            ->withRegistry('HH-0001')
            ->withPopulation(158)
            ->withCharter('colonial')
            ->withCallsign('Hadleys Hope')
            ->withFounded(2179)
            ->withGravity(0.9)
            ->withoutLiaison()
            ->asSelfSufficient()
            ->asEvacuated(false)
            ->includingPermit('mining')
        ;
    }

    public function benchBuildTwentyIngredients(): void
    {
        $this->colony->build();
    }
}
