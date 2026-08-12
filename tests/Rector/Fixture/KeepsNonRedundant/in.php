<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\Rector\Fixture\KeepsNonRedundant;

use Pizgariu\ImmutableTestBuilder\Contract\Attribute\NotMagic;
use Pizgariu\ImmutableTestBuilder\Contract\BuilderInterface;
use Pizgariu\ImmutableTestBuilder\Implementation\AbstractBuilder;

final class KeepsBuilder extends AbstractBuilder
{
    private string $name;

    private bool $ready;

    private string $label;

    private string $ownerName;

    public function withName(string $name): static
    {
        return $this->mutate(['name' => strtoupper($name)]);
    }

    public function asReady(): static
    {
        return $this->mutate(static function (self $builder): void {
            $builder->ready = true;
        });
    }

    public function withLabel(string $label = 'lv'): static
    {
        return $this->mutate(['label' => $label]);
    }

    public function forOwner(string $ownerName): static
    {
        return $this->mutate(['ownerName' => $ownerName]);
    }

    public function build(): string
    {
        return $this->name;
    }

    protected function seed(): void
    {
        $this->name = 'Nostromo';
        $this->ready = false;
        $this->label = 'lv';
        $this->ownerName = 'Weyland';
    }
}

final class NotABuilder
{
    private string $name = '';

    public function withName(string $name): self
    {
        $this->name = $name;

        return $this;
    }
}

final class SealedBuilder extends AbstractBuilder
{
    #[NotMagic]
    private string $secret;

    public function withSecret(string $secret): static
    {
        return $this->mutate(['secret' => $secret]);
    }

    public function build(): string
    {
        return $this->secret;
    }

    protected function seed(): void
    {
        $this->secret = 's';
    }
}

final class UnionFlagBuilder extends AbstractBuilder
{
    private bool|string $armed;

    public function asArmed(): static
    {
        return $this->mutate(['armed' => true]);
    }

    public function build(): string
    {
        return is_bool($this->armed) ? 'switch' : $this->armed;
    }

    protected function seed(): void
    {
        $this->armed = false;
    }
}

/**
 * @implements BuilderInterface<string>
 */
final class HandRolledBuilder implements BuilderInterface
{
    private string $name = 'n';

    public static function create(): static
    {
        return new self();
    }

    public function withName(string $name): static
    {
        return $this->mutate(['name' => $name]);
    }

    public function build(): string
    {
        return $this->name;
    }

    /**
     * @param array<string, mixed> $mutation
     */
    private function mutate(array $mutation): static
    {
        $clone = clone $this;

        foreach ($mutation as $property => $value) {
            $clone->{$property} = $value;
        }

        return $clone;
    }
}
