<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\Fixture;

/**
 * Autoloadable built type with no constructor, so it names no required
 * ingredients at all. A builder promising to cover it has promised something
 * unverifiable, and BuiltTypeCoverageRule has to say so rather than pass in
 * silence a reader would take for coverage.
 */
final class Derelict {}
