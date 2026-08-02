<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Benchmark;

use PhpBench\Attributes as Bench;
use Pizgariu\ImmutableTestBuilder\Tests\Fixture\SpaceshipBuilder;

/**
 * The kernel operations a suite pays for whatever the DSL looks like.
 *
 * create() seeds once, mutate() clones once per modifier, and a chain pays that
 * clone per link. The chain subject exists to prove the cost stays LINEAR in the
 * number of modifiers - the shallow clone copies a fixed set of ingredients, so
 * ten modifiers must land near ten times one, never near a hundred.
 */
#[Bench\Revs(5000)]
#[Bench\Iterations(5)]
#[Bench\Warmup(1)]
#[Bench\RetryThreshold(3.0)]
#[Bench\BeforeMethods('setUp')]
final class KernelBench
{
    private SpaceshipBuilder $builder;

    public function setUp(): void
    {
        $this->builder = SpaceshipBuilder::create();
    }

    public function benchCreate(): void
    {
        SpaceshipBuilder::create();
    }

    public function benchMutateThroughClosure(): void
    {
        $this->builder->withName('Nostromo');
    }

    public function benchMutateThroughMap(): void
    {
        $this->builder->withCrew(7);
    }

    public function benchChainOfOne(): void
    {
        $this->builder->withCrew(1);
    }

    public function benchChainOfTen(): void
    {
        $this->builder
            ->withCrew(1)
            ->withCrew(2)
            ->withCrew(3)
            ->withCrew(4)
            ->withCrew(5)
            ->withCrew(6)
            ->withCrew(7)
            ->withCrew(8)
            ->withCrew(9)
            ->withCrew(10)
        ;
    }

    public function benchBuild(): void
    {
        $this->builder->build();
    }
}
