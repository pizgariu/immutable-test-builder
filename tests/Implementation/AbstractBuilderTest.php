<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\Implementation;

use BadMethodCallException;
use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Pizgariu\ImmutableTestBuilder\Contract\Exception\UnbuildableState;
use Pizgariu\ImmutableTestBuilder\Tests\Fixture\Spaceship;
use Pizgariu\ImmutableTestBuilder\Tests\Fixture\SpaceshipBuilder;
use Pizgariu\ImmutableTestBuilder\Tests\Fixture\StationBuilder;
use ReflectionClass;

final class AbstractBuilderTest extends TestCase
{
    public function testCreateReturnsBuilderThatBuildsImmediately(): void
    {
        $ship = SpaceshipBuilder::create()->build();

        self::assertNotSame('', $ship->name);
        self::assertGreaterThan(0, $ship->fuel);
        self::assertGreaterThan(0, $ship->crew);
        self::assertFalse($ship->launched);
    }

    #[DataProvider('provideModifiers')]
    public function testModifierReturnsNewInstanceAndLeavesOriginalUntouched(
        Closure $modify,
        Closure $assertModified,
    ): void {
        $builder = SpaceshipBuilder::create();
        $before = $builder->build();

        $modified = $modify($builder);

        self::assertNotSame($builder, $modified);
        $assertModified($modified->build());

        $after = $builder->build();
        self::assertSame($before->name, $after->name);
        self::assertSame($before->fuel, $after->fuel);
        self::assertSame($before->crew, $after->crew);
        self::assertSame($before->launched, $after->launched);
    }

    /**
     * @return iterable<string, array{modify: Closure(SpaceshipBuilder): SpaceshipBuilder, assertModified: Closure(Spaceship): void}>
     */
    public static function provideModifiers(): iterable
    {
        yield 'withName' => [
            'modify' => static fn (SpaceshipBuilder $builder): SpaceshipBuilder => $builder->withName('Nostromo'),
            'assertModified' => static function (Spaceship $ship): void {
                self::assertSame('Nostromo', $ship->name);
            },
        ];

        yield 'withCrew' => [
            'modify' => static fn (SpaceshipBuilder $builder): SpaceshipBuilder => $builder->withCrew(7),
            'assertModified' => static function (Spaceship $ship): void {
                self::assertSame(7, $ship->crew);
            },
        ];

        yield 'asLaunched' => [
            'modify' => static fn (SpaceshipBuilder $builder): SpaceshipBuilder => $builder->asLaunched(),
            'assertModified' => static function (Spaceship $ship): void {
                self::assertTrue($ship->launched);
            },
        ];

        yield 'withoutFuel' => [
            'modify' => static fn (SpaceshipBuilder $builder): SpaceshipBuilder => $builder->withoutFuel(),
            'assertModified' => static function (Spaceship $ship): void {
                self::assertSame(0, $ship->fuel);
            },
        ];
    }

    public function testBranchingFromOneTrunkBuilderProducesIndependentResults(): void
    {
        $base = SpaceshipBuilder::create()->withName('Nostromo')->withCrew(4);

        $launched = $base->asLaunched();
        $drained = $base->withoutFuel();

        $baseShip = $base->build();
        $launchedShip = $launched->build();
        $drainedShip = $drained->build();

        self::assertFalse($baseShip->launched);
        self::assertGreaterThan(0, $baseShip->fuel);
        self::assertTrue($launchedShip->launched);
        self::assertSame($baseShip->fuel, $launchedShip->fuel);
        self::assertFalse($drainedShip->launched);
        self::assertSame(0, $drainedShip->fuel);
        self::assertSame('Nostromo', $baseShip->name);
        self::assertSame('Nostromo', $launchedShip->name);
        self::assertSame('Nostromo', $drainedShip->name);
        self::assertSame(4, $drainedShip->crew);
    }

    public function testBuildThrowsWhenLaunchedWithEmptyFuelTank(): void
    {
        $builder = SpaceshipBuilder::create()->withoutFuel()->asLaunched();

        try {
            $builder->build();
            self::fail('Expected UnbuildableState to be thrown.');
        } catch (UnbuildableState $exception) {
            self::assertStringContainsString('SpaceshipBuilder', $exception->getMessage());
            self::assertStringContainsString('contradiction', $exception->getMessage());
            self::assertStringContainsString('fuel tank is empty', $exception->getMessage());
            self::assertStringContainsString('Drop withoutFuel() or skip asLaunched().', $exception->getMessage());
        }
    }

    public function testMapMutationRefusesAnUndeclaredKey(): void
    {
        $builder = StationBuilder::create();

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('cannot write $nane');

        $builder->withMistypedName('Verlaine');
    }

    public function testMapMutationRefusesAParentPrivateProperty(): void
    {
        $builder = StationBuilder::create();

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('cannot write $designation');

        $builder->forDesignation('LV-426');
    }

    public function testConstructorIsNotPubliclyInstantiable(): void
    {
        $constructor = (new ReflectionClass(SpaceshipBuilder::class))->getConstructor();

        self::assertNotNull($constructor);
        self::assertTrue($constructor->isProtected());
        self::assertTrue($constructor->isFinal());
    }
}
