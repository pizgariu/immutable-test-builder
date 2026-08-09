<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\Fixture;

/**
 * Autoloadable built type whose required ingredients are named after the shapes
 * AbstractStationBuilder holds - a protected static and a protected readonly.
 * A builder over this type can see both and write neither, which is what proves
 * BuiltTypeCoverageRule asks whether a property is writable rather than present.
 */
final readonly class Beacon
{
    public function __construct(
        public string $name,
        public string $registry,
        public string $commissioned,
    ) {}
}
