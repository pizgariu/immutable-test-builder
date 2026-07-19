<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\Contract\Exception;

use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Pizgariu\ImmutableTestBuilder\Contract\Exception\UnbuildableState;

final class UnbuildableStateTest extends TestCase
{
    public function testMissingComposesMessageFromShortNameIngredientAndAdvice(): void
    {
        $exception = UnbuildableState::missing(
            'Acme\Tests\Builder\OrderBuilder',
            'a delivery address',
            'Call withDeliveryAddress() before build().',
        );

        self::assertSame(
            'OrderBuilder cannot build yet - missing a delivery address. Call withDeliveryAddress() before build().',
            $exception->getMessage(),
        );
    }

    public function testContradictionComposesMessageFromShortNameConflictAndWayOut(): void
    {
        $exception = UnbuildableState::contradiction(
            'Acme\Tests\Builder\OrderBuilder',
            'asPaid() combined with withoutPayment()',
            'Drop one of the two calls.',
        );

        self::assertSame(
            'OrderBuilder was driven into a contradiction - asPaid() combined with withoutPayment(). Drop one of the two calls.',
            $exception->getMessage(),
        );
    }

    public function testMissingCreatesLogicException(): void
    {
        $exception = UnbuildableState::missing('OrderBuilder', 'a customer', 'Call withCustomer().');

        self::assertInstanceOf(LogicException::class, $exception);
    }

    public function testContradictionCreatesLogicException(): void
    {
        $exception = UnbuildableState::contradiction('OrderBuilder', 'empty cart marked as shipped', 'Append an item.');

        self::assertInstanceOf(LogicException::class, $exception);
    }

    #[DataProvider('provideBuilderClassNames')]
    public function testMissingDerivesShortNameFromBuilderClass(string $builderClass, string $shortName): void
    {
        $exception = UnbuildableState::missing($builderClass, 'a customer', 'Call withCustomer().');

        self::assertStringStartsWith($shortName . ' cannot build yet', $exception->getMessage());
    }

    #[DataProvider('provideBuilderClassNames')]
    public function testContradictionDerivesShortNameFromBuilderClass(string $builderClass, string $shortName): void
    {
        $exception = UnbuildableState::contradiction($builderClass, 'a conflict', 'A way out.');

        self::assertStringStartsWith($shortName . ' was driven into a contradiction', $exception->getMessage());
    }

    /**
     * @return iterable<string, array{builderClass: string, shortName: string}>
     */
    public static function provideBuilderClassNames(): iterable
    {
        yield 'namespaced class' => [
            'builderClass' => 'Acme\Tests\Builder\CustomerBuilder',
            'shortName' => 'CustomerBuilder',
        ];

        yield 'namespaced class with leading backslash' => [
            'builderClass' => '\Acme\Tests\Builder\CustomerBuilder',
            'shortName' => 'CustomerBuilder',
        ];

        yield 'global class' => [
            'builderClass' => 'CustomerBuilder',
            'shortName' => 'CustomerBuilder',
        ];

        yield 'global class with leading backslash' => [
            'builderClass' => '\CustomerBuilder',
            'shortName' => 'CustomerBuilder',
        ];
    }
}
