<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\Example;

/**
 * Hydration source for the from* modifier in the DSL tour.
 */
final readonly class Applicant
{
    public function __construct(
        public string $email,
        public string $firstName,
        public string $lastName,
    ) {}
}
