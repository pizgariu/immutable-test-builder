<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder;

use Faker\Factory;
use Faker\Generator;
use InvalidArgumentException;
use ReflectionClass;

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
     *
     * @throws InvalidArgumentException when Faker ships no providers for the
     *                                  locale and would silently fall back to
     *                                  the default locale's data
     */
    public static function locale(string $locale = self::DEFAULT_LOCALE): Generator
    {
        if (!isset(self::$generators[$locale])) {
            self::rejectUnknownLocale($locale);
        }

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

    private static function rejectUnknownLocale(string $locale): void
    {
        if (self::DEFAULT_LOCALE === $locale) {
            return;
        }

        $factoryFile = (new ReflectionClass(Factory::class))->getFileName();

        if (false === $factoryFile) {
            return;
        }

        if (!is_dir(sprintf('%s/Provider/%s', dirname($factoryFile), $locale))) {
            throw new InvalidArgumentException(sprintf(
                'Unknown Faker locale "%s" - Faker ships no providers for it and would silently fall back to %s.',
                $locale,
                self::DEFAULT_LOCALE,
            ));
        }
    }
}
