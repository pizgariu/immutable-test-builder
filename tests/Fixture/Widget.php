<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\Fixture;

/**
 * Autoloadable built type for the BuiltTypeCoverageRule fixtures. A rule reads
 * nothing from a class declared inside a data file, which is why it lives here.
 */
final readonly class Widget
{
    public function __construct(
        public string $sku,
        public int $quantity,
        public ?string $note = null,
    ) {}
}
