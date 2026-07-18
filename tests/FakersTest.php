<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Pizgariu\ImmutableTestBuilder\Fakers;
use ReflectionClass;

final class FakersTest extends TestCase
{
    protected function setUp(): void
    {
        Fakers::flush();
    }

    protected function tearDown(): void
    {
        Fakers::flush();
    }

    #[DataProvider('provideLocales')]
    public function testLocaleReturnsIdenticalGeneratorPerLocale(string $locale): void
    {
        self::assertSame(Fakers::locale($locale), Fakers::locale($locale));
    }

    /**
     * @return iterable<string, array{locale: string}>
     */
    public static function provideLocales(): iterable
    {
        yield 'default locale' => ['locale' => Fakers::DEFAULT_LOCALE];
        yield 'polish locale' => ['locale' => 'pl_PL'];
        yield 'romanian locale' => ['locale' => 'ro_RO'];
    }

    #[DataProvider('provideUnknownLocales')]
    public function testLocaleRejectsUnknownLocale(string $locale): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($locale);

        Fakers::locale($locale);
    }

    /**
     * @return iterable<string, array{locale: string}>
     */
    public static function provideUnknownLocales(): iterable
    {
        yield 'hyphen instead of underscore' => ['locale' => 'pl-PL'];
        yield 'made up locale' => ['locale' => 'xx_XX'];
    }

    public function testLocaleReturnsDifferentGeneratorsForDifferentLocales(): void
    {
        self::assertNotSame(Fakers::locale('en_US'), Fakers::locale('pl_PL'));
    }

    public function testFlushDropsMemoizedGenerators(): void
    {
        $generator = Fakers::locale('en_US');

        Fakers::flush();

        self::assertNotSame($generator, Fakers::locale('en_US'));
    }

    public function testLocaleWithoutArgumentUsesDefaultLocale(): void
    {
        self::assertSame(Fakers::locale(Fakers::DEFAULT_LOCALE), Fakers::locale());
    }

    public function testCannotBeInstantiated(): void
    {
        $constructor = (new ReflectionClass(Fakers::class))->getConstructor();

        self::assertNotNull($constructor);
        self::assertTrue($constructor->isPrivate());
    }
}
