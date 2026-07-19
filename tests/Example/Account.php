<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\Example;

/**
 * Ownership context for the for* modifier in the DSL tour.
 */
final readonly class Account
{
    public function __construct(public int $id) {}
}
