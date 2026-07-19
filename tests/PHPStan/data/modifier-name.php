<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Data\ModifierName;

use Pizgariu\ImmutableTestBuilder\Implementation\AbstractBuilder;

/**
 * @extends AbstractBuilder<string>
 */
final class AccountBuilder extends AbstractBuilder
{
    private string $owner;

    private bool $active = true;

    protected function seed(): void
    {
        $this->owner = 'Ripley';
    }

    public function withOwner(string $owner): static
    {
        return $this->mutate(static function (self $builder) use ($owner): void {
            $builder->owner = $owner;
        });
    }

    public function setOwner(string $owner): static
    {
        return $this->mutate(static function (self $builder) use ($owner): void {
            $builder->owner = $owner;
        });
    }

    public function makeInactive(): static
    {
        return $this->mutate(static function (self $builder): void {
            $builder->active = false;
        });
    }

    public function addOwner(string $owner): static
    {
        return $this->withOwner($owner);
    }

    public function normalizedOwner(): string
    {
        return strtolower($this->owner);
    }

    public static function fromScratch(): static
    {
        return static::create();
    }

    protected function helper(): string
    {
        return $this->owner;
    }

    public function build(): string
    {
        return $this->active ? $this->owner : strtolower($this->owner);
    }
}
