<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\Implementation;

use BadMethodCallException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Pizgariu\ImmutableTestBuilder\Tests\Fixture\FreighterBuilder;
use Pizgariu\ImmutableTestBuilder\Tests\Fixture\RosterBuilder;
use Pizgariu\ImmutableTestBuilder\Tests\Fixture\ShuttleBuilder;

/**
 * __call is thin - it hands the call to ModifierResolver and funnels the result
 * through mutate(). ModifierResolverTest pins the derivation itself. This pins
 * the wiring - a magic call clones like any modifier, chains and propagates the
 * resolver's refusal.
 */
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

    public function testMagicChainsLikeAnyHandwrittenModifier(): void
    {
        $freighter = FreighterBuilder::create()->asArmed()->withoutArmed()->build();

        self::assertFalse($freighter['armed']);
    }

    public function testAsTakesAnExplicitBoolStatically(): void
    {
        $freighter = FreighterBuilder::create()->asArmed(true)->asArmed(false)->build();

        self::assertFalse($freighter['armed']);
    }

    public function testAsClearsANullableFlagStatically(): void
    {
        self::assertNull(FreighterBuilder::create()->asMothballed(null)->build()['mothballed']);
    }

    public function testMagicResolvesAnIrregularPluralThroughTheAttribute(): void
    {
        $roster = RosterBuilder::create()->includingPerson('Kane')->excludingPerson('Ripley')->build();

        self::assertSame(['Kane'], $roster['people']);
    }

    public function testIncludingStartsANullableCollectionFresh(): void
    {
        self::assertSame(['crate'], ShuttleBuilder::create()->includingCargo('crate')->build()['cargo']);
    }

    public function testExcludingLeavesANullCollectionNull(): void
    {
        self::assertNull(ShuttleBuilder::create()->excludingCargo('crate')->build()['cargo']);
    }

    public function testWithoutPrefersNullForANullableCollection(): void
    {
        self::assertNull(ShuttleBuilder::create()->includingCargo('crate')->withoutCargo()->build()['cargo']);
    }

    public function testTheShallowCloneSharesAnObjectIngredientWithTheTrunk(): void
    {
        $trunk = ShuttleBuilder::create();
        $branch = $trunk->withName('Narcissus')->asDocked(false);

        self::assertSame($trunk->build()['departedAt'], $branch->build()['departedAt']);
    }

    /**
     * The derivation is memoised, so this pins the one thing a memo can get
     * wrong. $cargo is a nullable collection on the shuttle and a plain int on
     * the freighter, so the same method name has to reach a different property
     * on each and refuse on one of them. A key that forgot the class would let
     * whichever builder ran first answer for both.
     */
    public function testTheDerivationIsKeyedPerClassAndNotPerMethodName(): void
    {
        $shuttle = ShuttleBuilder::create()->includingCargo('crate');

        try {
            // @phpstan-ignore method.notFound (the extension refuses to advertise this for the same reason the writer refuses to perform it, which is the other half of the point)
            FreighterBuilder::create()->includingCargo(5);
            self::fail('includingCargo() must be refused on the freighter, where $cargo is an int');
        } catch (BadMethodCallException $refusal) {
            self::assertStringContainsString('appends to a collection', $refusal->getMessage());
        }

        self::assertSame(['crate'], $shuttle->build()['cargo']);
        self::assertSame(['crate', 'more'], $shuttle->includingCargo('more')->build()['cargo']);
    }

    public function testMagicRefusesNamedArguments(): void
    {
        $builder = FreighterBuilder::create();

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('does not accept named arguments');

        $builder->asArmed(armed: false);
    }

    /**
     * @param array<int, mixed> $arguments
     */
    #[DataProvider('provideRejectedCalls')]
    public function testMagicPropagatesTheResolverRefusal(string $method, array $arguments, string $fragment): void
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
        yield 'missing property' => ['method' => 'withSerial', 'arguments' => ['s'], 'fragment' => 'no matching property'];
    }
}
