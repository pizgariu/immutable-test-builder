<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\Example;

/**
 * Value object produced by UserBuilder; part of the library's living documentation.
 */
final readonly class User
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        public string $name,
        public string $email,
        public array $roles,
        public bool $active,
    ) {
    }
}
