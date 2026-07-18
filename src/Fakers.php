<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder;

use Faker\Factory;
use Faker\Generator;

/**
 * Process-wide, memoized Faker generators keyed by locale.
 */
final class Fakers
{
    public const string DEFAULT_LOCALE = 'en_US';

    /** @var array<string, Generator> */
    private static array $generators = [];

    private function __construct()
    {
    }

    /**
     * Returns the same Generator instance per locale within a process.
     */
    public static function locale(string $locale = self::DEFAULT_LOCALE): Generator
    {
        return self::$generators[$locale] ??= Factory::create($locale);
    }

    /**
     * Drops the memoized instances so tests that reseed or replace
     * generators stay isolated from each other.
     */
    public static function flush(): void
    {
        self::$generators = [];
    }
}
