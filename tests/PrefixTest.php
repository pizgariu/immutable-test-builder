<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Pizgariu\ImmutableTestBuilder\Contract\Enum\Prefix;

final class PrefixTest extends TestCase
{
    #[DataProvider('provideMethodNames')]
    public function testOfMethodResolvesThePrefix(string $methodName, ?Prefix $prefix): void
    {
        self::assertSame($prefix, Prefix::ofMethod($methodName));
    }

    /**
     * @return iterable<string, array{methodName: string, prefix: Prefix|null}>
     */
    public static function provideMethodNames(): iterable
    {
        yield 'without is not swallowed by with' => ['methodName' => 'withoutFuel', 'prefix' => Prefix::Without];
        yield 'with' => ['methodName' => 'withName', 'prefix' => Prefix::With];
        yield 'from is not shadowed by for' => ['methodName' => 'fromOrder', 'prefix' => Prefix::From];
        yield 'for' => ['methodName' => 'forCustomer', 'prefix' => Prefix::For];
        yield 'as' => ['methodName' => 'asLaunched', 'prefix' => Prefix::As];
        yield 'having' => ['methodName' => 'havingAge', 'prefix' => Prefix::Having];
        yield 'including' => ['methodName' => 'includingRole', 'prefix' => Prefix::Including];
        yield 'excluding' => ['methodName' => 'excludingRole', 'prefix' => Prefix::Excluding];
        yield 'outside the DSL' => ['methodName' => 'setName', 'prefix' => null];
        yield 'prefix without an uppercase boundary' => ['methodName' => 'withdraw', 'prefix' => null];
        yield 'bare prefix' => ['methodName' => 'with', 'prefix' => null];
    }

    #[DataProvider('provideParameterAppetites')]
    public function testTakesParameters(Prefix $prefix, bool $takesParameters): void
    {
        self::assertSame($takesParameters, $prefix->takesParameters());
    }

    /**
     * @return iterable<string, array{prefix: Prefix, takesParameters: bool}>
     */
    public static function provideParameterAppetites(): iterable
    {
        yield 'with feeds' => ['prefix' => Prefix::With, 'takesParameters' => true];
        yield 'from feeds' => ['prefix' => Prefix::From, 'takesParameters' => true];
        yield 'for feeds' => ['prefix' => Prefix::For, 'takesParameters' => true];
        yield 'having feeds' => ['prefix' => Prefix::Having, 'takesParameters' => true];
        yield 'including feeds' => ['prefix' => Prefix::Including, 'takesParameters' => true];
        yield 'excluding feeds' => ['prefix' => Prefix::Excluding, 'takesParameters' => true];
        yield 'without names the change' => ['prefix' => Prefix::Without, 'takesParameters' => false];
        yield 'as names the change' => ['prefix' => Prefix::As, 'takesParameters' => false];
    }

    /**
     * @param list<string> $candidates
     */
    #[DataProvider('providePropertyCandidates')]
    public function testPropertyCandidatesSpeakSingularAndSimplePlural(Prefix $prefix, string $methodName, array $candidates): void
    {
        self::assertSame($candidates, $prefix->propertyCandidates($methodName));
    }

    /**
     * @return iterable<string, array{prefix: Prefix, methodName: string, candidates: list<string>}>
     */
    public static function providePropertyCandidates(): iterable
    {
        yield 'including tries the plural' => ['prefix' => Prefix::Including, 'methodName' => 'includingRole', 'candidates' => ['role', 'roles']];
        yield 'excluding tries the plural' => ['prefix' => Prefix::Excluding, 'methodName' => 'excludingDecal', 'candidates' => ['decal', 'decals']];
        yield 'with speaks singular' => ['prefix' => Prefix::With, 'methodName' => 'withName', 'candidates' => ['name']];
    }

    public function testMagicAndParameterlessSplitTheGrammar(): void
    {
        self::assertSame([Prefix::With, Prefix::Without, Prefix::As, Prefix::Including, Prefix::Excluding], Prefix::magic());
        self::assertSame([Prefix::Without, Prefix::As], Prefix::parameterless());

        foreach (Prefix::magic() as $prefix) {
            self::assertTrue($prefix->autoImplementable());
        }

        self::assertFalse(Prefix::From->autoImplementable());
        self::assertFalse(Prefix::For->autoImplementable());
        self::assertFalse(Prefix::Having->autoImplementable());
    }
}
