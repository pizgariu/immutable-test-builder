<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\Resolver;

use BadMethodCallException;
use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Pizgariu\ImmutableTestBuilder\Implementation\Resolver\ModifierResolver;
use Pizgariu\ImmutableTestBuilder\Tests\Fixture\FreighterBuilder;
use Pizgariu\ImmutableTestBuilder\Tests\Fixture\GadgetBuilder;

final class ModifierResolverTest extends TestCase
{
    public function testResolveReturnsAClosureThatWritesTheDerivedProperty(): void
    {
        $freighter = FreighterBuilder::create();

        $write = ModifierResolver::resolve(FreighterBuilder::class, 'withCallsign', ['Nostromo']);

        self::assertInstanceOf(Closure::class, $write);

        $write($freighter);

        self::assertSame('Nostromo', $freighter->build()['callsign']);
    }

    #[DataProvider('provideEmptiedProperties')]
    public function testWithoutInfersTheEmptyValueFromThePropertyType(string $method, string $field, mixed $emptied): void
    {
        $freighter = FreighterBuilder::create();

        ModifierResolver::resolve(FreighterBuilder::class, $method, [])($freighter);

        self::assertSame($emptied, $freighter->build()[$field]);
    }

    /**
     * @return iterable<string, array{method: string, field: string, emptied: mixed}>
     */
    public static function provideEmptiedProperties(): iterable
    {
        yield 'nullable string goes null' => ['method' => 'withoutPilot', 'field' => 'pilot', 'emptied' => null];
        yield 'string goes empty' => ['method' => 'withoutCallsign', 'field' => 'callsign', 'emptied' => ''];
        yield 'int goes zero' => ['method' => 'withoutCargo', 'field' => 'cargo', 'emptied' => 0];
        yield 'float goes zero' => ['method' => 'withoutFuel', 'field' => 'fuel', 'emptied' => 0.0];
        yield 'array goes empty' => ['method' => 'withoutDecals', 'field' => 'decals', 'emptied' => []];
    }

    public function testAsDerivesTheRaisedFlag(): void
    {
        $freighter = FreighterBuilder::create();

        ModifierResolver::resolve(FreighterBuilder::class, 'asArmed', [])($freighter);

        self::assertTrue($freighter->build()['armed']);
    }

    public function testIncludingResolvesThePluralPropertyAndAppends(): void
    {
        $freighter = FreighterBuilder::create();

        ModifierResolver::resolve(FreighterBuilder::class, 'includingDecal', ['flame'])($freighter);

        self::assertSame(['stripe', 'flame'], $freighter->build()['decals']);
    }

    public function testExcludingRemovesWithoutReplacingTheCollection(): void
    {
        $freighter = FreighterBuilder::create();

        ModifierResolver::resolve(FreighterBuilder::class, 'excludingDecal', ['stripe'])($freighter);

        self::assertSame([], $freighter->build()['decals']);
    }

    /**
     * @param class-string $class
     * @param array<int, mixed> $arguments
     */
    #[DataProvider('provideRefusals')]
    public function testRefusesWhatItCannotDerive(string $class, string $method, array $arguments, string $fragment): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage($fragment);

        ModifierResolver::resolve($class, $method, $arguments);
    }

    /**
     * @return iterable<string, array{class: class-string, method: string, arguments: array<int, mixed>, fragment: string}>
     */
    public static function provideRefusals(): iterable
    {
        yield 'unknown prefix' => ['class' => FreighterBuilder::class, 'method' => 'launchTowards', 'arguments' => ['earth'], 'fragment' => 'no DSL prefix matches'];
        yield 'from is never magic' => ['class' => FreighterBuilder::class, 'method' => 'fromManifest', 'arguments' => ['m'], 'fragment' => 'never magic'];
        yield 'for is never magic' => ['class' => FreighterBuilder::class, 'method' => 'forFleet', 'arguments' => ['f'], 'fragment' => 'never magic'];
        yield 'having is never magic' => ['class' => FreighterBuilder::class, 'method' => 'havingCrew', 'arguments' => [3], 'fragment' => 'never magic'];
        yield 'missing property' => ['class' => FreighterBuilder::class, 'method' => 'withSerial', 'arguments' => ['s'], 'fragment' => 'no matching property'];
        yield 'with wants an argument' => ['class' => FreighterBuilder::class, 'method' => 'withCallsign', 'arguments' => [], 'fragment' => 'takes exactly 1 argument'];
        yield 'as wants none' => ['class' => FreighterBuilder::class, 'method' => 'asArmed', 'arguments' => [true], 'fragment' => 'takes exactly 0 argument'];
        yield 'uninferrable empty value' => ['class' => GadgetBuilder::class, 'method' => 'withoutInstalledAt', 'arguments' => [], 'fragment' => 'Cannot infer an empty value'];
        yield 'as on a non-bool property' => ['class' => FreighterBuilder::class, 'method' => 'asCargo', 'arguments' => [], 'fragment' => 'raises a boolean flag'];
    }
}
