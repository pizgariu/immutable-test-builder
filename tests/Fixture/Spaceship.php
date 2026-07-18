<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\Fixture;

/**
 * Tiny value object exercised by the kernel tests.
 */
final readonly class Spaceship
{
    public function __construct(
        public string $name,
        public int $fuel,
        public int $crew,
        public bool $launched,
    ) {}
}
