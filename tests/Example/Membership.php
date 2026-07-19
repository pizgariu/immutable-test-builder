<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\Example;

/**
 * The value object produced by MembershipBuilder, the full DSL tour.
 */
final readonly class Membership
{
    /**
     * @param list<string> $tags
     */
    public function __construct(
        public string $email,
        public string $firstName,
        public string $lastName,
        public int $accountId,
        public bool $active,
        public array $tags,
    ) {}
}
