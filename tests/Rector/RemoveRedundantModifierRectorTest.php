<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\Rector;

use PHPUnit\Framework\TestCase;
use Pizgariu\ImmutableTestBuilder\Rector\RemoveRedundantModifierRector;
use RuntimeException;

/**
 * Drives the real rector binary over the fixtures instead of extending
 * AbstractRectorTestCase. Rector bundles one php-parser, PHPStan's phar bundles
 * another and PHPUnit 12 eagerly loads a third through its coverage tooling, so
 * a process already running this suite can never host Rector's. The binary gets
 * its own process, which is also exactly how every consumer runs it.
 *
 * Every fixture is plain valid PHP an IDE reads without complaint. A case is a
 * directory holding in.php and, when the rule must rewrite it, an out.php.expected
 * beside it. A case without the expected file must come out untouched.
 *
 * @covers \Pizgariu\ImmutableTestBuilder\Rector\RemoveRedundantModifierRector
 */
final class RemoveRedundantModifierRectorTest extends TestCase
{
    public function testReplacesDerivableModifiersWithMethodTags(): void
    {
        $before = self::fixture('RedundantModifiers/in.php');
        $after = self::fixture('RedundantModifiers/out.php.expected');

        self::assertNotSame($before, $after, 'the fixture must expect a rewrite');
        self::assertSame($after, self::refactored($before));
    }

    public function testLeavesEveryModifierTheKernelCannotDerive(): void
    {
        $before = self::fixture('KeepsNonRedundant/in.php');

        self::assertSame($before, self::refactored($before));
    }

    private static function fixture(string $name): string
    {
        $raw = file_get_contents(__DIR__ . '/Fixture/' . $name);

        self::assertIsString($raw);

        return $raw;
    }

    private static function refactored(string $code): string
    {
        $scratch = sys_get_temp_dir() . '/itb-rector-' . bin2hex(random_bytes(6));

        if (!mkdir($scratch)) {
            throw new RuntimeException('cannot create the scratch directory ' . $scratch);
        }

        $subject = $scratch . '/Fixture.php';
        file_put_contents($subject, $code);

        $root = dirname(__DIR__, 2);
        $command = sprintf(
            'cd %s && %s vendor/bin/rector process %s --config %s --clear-cache --no-progress-bar --no-ansi 2>&1',
            escapeshellarg($root),
            escapeshellarg(PHP_BINARY),
            escapeshellarg($subject),
            escapeshellarg($root . '/tests/Rector/config/configured_rule.php'),
        );

        exec($command, $output, $exitCode);

        $result = file_get_contents($subject);

        unlink($subject);
        rmdir($scratch);

        if (0 !== $exitCode || !is_string($result)) {
            throw new RuntimeException(sprintf(
                'rector exited with %d over %s. Output was %s',
                $exitCode,
                RemoveRedundantModifierRector::class,
                implode("\n", $output),
            ));
        }

        return $result;
    }
}
