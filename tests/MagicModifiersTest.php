<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests;

use BadMethodCallException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Pizgariu\ImmutableTestBuilder\Tests\Fixture\FreighterBuilder;

final class MagicModifiersTest extends TestCase
{
    public function testWithAssignsTheArgumentAndLeavesTheOriginalUntouched(): void
    {
        $builder = FreighterBuilder::create();
        $before = $builder->build();

        $renamed = $builder->withCallsign('Nostromo');

        self::assertNotSame($builder, $renamed);
        self::assertSame('Nostromo', $renamed->build()['callsign']);
        self::assertSame($before['callsign'], $builder->build()['callsign']);
    }

    #[DataProvider('provideEmptiedProperties')]
    public function testWithoutInfersTheEmptyValueFromThePropertyType(string $modifier, string $field, mixed $emptied): void
    {
        $freighter = FreighterBuilder::create()->{$modifier}()->build();

        self::assertSame($emptied, $freighter[$field]);
    }

    /**
     * @return iterable<string, array{modifier: string, field: string, emptied: mixed}>
     */
    public static function provideEmptiedProperties(): iterable
    {
        yield 'nullable string goes null' => ['modifier' => 'withoutPilot', 'field' => 'pilot', 'emptied' => null];
        yield 'string goes empty' => ['modifier' => 'withoutCallsign', 'field' => 'callsign', 'emptied' => ''];
        yield 'int goes zero' => ['modifier' => 'withoutCargo', 'field' => 'cargo', 'emptied' => 0];
        yield 'float goes zero' => ['modifier' => 'withoutFuel', 'field' => 'fuel', 'emptied' => 0.0];
        yield 'array goes empty' => ['modifier' => 'withoutDecals', 'field' => 'decals', 'emptied' => []];
    }

    public function testAsRaisesTheFlag(): void
    {
        self::assertTrue(FreighterBuilder::create()->asArmed()->build()['armed']);
    }

    public function testIncludingResolvesThePluralPropertyAndAppends(): void
    {
        self::assertSame(['stripe', 'flame'], FreighterBuilder::create()->includingDecal('flame')->build()['decals']);
    }

    public function testExcludingRemovesWithoutReplacingTheCollection(): void
    {
        self::assertSame(['flame'], FreighterBuilder::create()->includingDecal('flame')->excludingDecal('stripe')->build()['decals']);
    }

    public function testMagicChainsLikeAnyHandwrittenModifier(): void
    {
        $freighter = FreighterBuilder::create()->asArmed()->withoutArmed()->build();

        self::assertFalse($freighter['armed']);
    }

    /**
     * @param array<int, mixed> $arguments
     */
    #[DataProvider('provideRejectedCalls')]
    public function testMagicRefusesWhatItCannotHonestlyImplement(string $method, array $arguments, string $fragment): void
    {
        $builder = FreighterBuilder::create();

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage($fragment);

        $builder->{$method}(...$arguments);
    }

    /**
     * @return iterable<string, array{method: string, arguments: array<int, mixed>, fragment: string}>
     */
    public static function provideRejectedCalls(): iterable
    {
        yield 'unknown prefix' => ['method' => 'launchTowards', 'arguments' => ['earth'], 'fragment' => 'no DSL prefix matches'];
        yield 'from is never magic' => ['method' => 'fromManifest', 'arguments' => ['m'], 'fragment' => 'never magic'];
        yield 'for is never magic' => ['method' => 'forFleet', 'arguments' => ['f'], 'fragment' => 'never magic'];
        yield 'having is never magic' => ['method' => 'havingCrew', 'arguments' => [3], 'fragment' => 'never magic'];
        yield 'missing property' => ['method' => 'withSerial', 'arguments' => ['s'], 'fragment' => 'no matching property'];
        yield 'with wants an argument' => ['method' => 'withCallsign', 'arguments' => [], 'fragment' => 'takes exactly 1 argument'];
        yield 'as wants none' => ['method' => 'asArmed', 'arguments' => [true], 'fragment' => 'takes exactly 0 argument'];
    }
}
