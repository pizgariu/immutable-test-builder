<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests;

use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Pizgariu\ImmutableTestBuilder\Exception\UnbuildableState;
use Pizgariu\ImmutableTestBuilder\Fakers;
use Pizgariu\ImmutableTestBuilder\Tests\Fixture\Spaceship;
use Pizgariu\ImmutableTestBuilder\Tests\Fixture\SpaceshipBuilder;
use ReflectionClass;

final class BaseBuilderTest extends TestCase
{
    protected function setUp(): void
    {
        Fakers::flush();
    }

    protected function tearDown(): void
    {
        Fakers::flush();
    }

    public function testCreateReturnsBuilderThatBuildsImmediately(): void
    {
        $ship = SpaceshipBuilder::create()->build();

        self::assertNotSame('', $ship->name);
        self::assertGreaterThan(0, $ship->fuel);
        self::assertGreaterThan(0, $ship->crew);
        self::assertFalse($ship->launched);
    }

    public function testCreateFixesDefaultLocale(): void
    {
        self::assertSame(Fakers::DEFAULT_LOCALE, SpaceshipBuilder::create()->locale());
    }

    public function testCreateInSeedsFromRequestedLocaleGenerator(): void
    {
        $faker = Fakers::locale('pl_PL');
        $faker->seed(20260718);
        $expectedName = $faker->name();
        $expectedFuel = $faker->numberBetween(1, 100);
        $expectedCrew = $faker->numberBetween(1, 12);

        $faker->seed(20260718);
        $builder = SpaceshipBuilder::createIn('pl_PL');
        $ship = $builder->build();

        self::assertSame('pl_PL', $builder->locale());
        self::assertSame($expectedName, $ship->name);
        self::assertSame($expectedFuel, $ship->fuel);
        self::assertSame($expectedCrew, $ship->crew);
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
     * @return iterable<string, array{Closure(SpaceshipBuilder): SpaceshipBuilder, Closure(Spaceship): void}>
     */
    public static function provideModifiers(): iterable
    {
        yield 'withName' => [
            static fn (SpaceshipBuilder $builder): SpaceshipBuilder => $builder->withName('Nostromo'),
            static function (Spaceship $ship): void {
                self::assertSame('Nostromo', $ship->name);
            },
        ];

        yield 'withCrew' => [
            static fn (SpaceshipBuilder $builder): SpaceshipBuilder => $builder->withCrew(7),
            static function (Spaceship $ship): void {
                self::assertSame(7, $ship->crew);
            },
        ];

        yield 'asLaunched' => [
            static fn (SpaceshipBuilder $builder): SpaceshipBuilder => $builder->asLaunched(),
            static function (Spaceship $ship): void {
                self::assertTrue($ship->launched);
            },
        ];

        yield 'withoutFuel' => [
            static fn (SpaceshipBuilder $builder): SpaceshipBuilder => $builder->withoutFuel(),
            static function (Spaceship $ship): void {
                self::assertSame(0, $ship->fuel);
            },
        ];
    }

    public function testBranchingFromOneBaseBuilderProducesIndependentResults(): void
    {
        $base = SpaceshipBuilder::create()->withName('Rocinante')->withCrew(4);

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
        self::assertSame('Rocinante', $baseShip->name);
        self::assertSame('Rocinante', $launchedShip->name);
        self::assertSame('Rocinante', $drainedShip->name);
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

    public function testConstructorIsNotPubliclyInstantiable(): void
    {
        $constructor = (new ReflectionClass(SpaceshipBuilder::class))->getConstructor();

        self::assertNotNull($constructor);
        self::assertTrue($constructor->isProtected());
        self::assertTrue($constructor->isFinal());
    }
}
