<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Data\ModifierDelegates;

use Pizgariu\ImmutableTestBuilder\Implementation\AbstractBuilder;

/**
 * @extends AbstractBuilder<string>
 */
final class ShipmentBuilder extends AbstractBuilder
{
    private string $carrier;

    private int $parcels = 1;

    protected function seed(): void
    {
        $this->carrier = 'Weyland-Yutani';
    }

    public function withCarrier(string $carrier): static
    {
        return $this->mutate(static function (self $builder) use ($carrier): void {
            $builder->carrier = $carrier;
        });
    }

    public function withParcels(int $parcels): self
    {
        return $this->mutate(static function (self $builder) use ($parcels): void {
            $builder->parcels = $parcels;
        });
    }

    public function asExpress(): static
    {
        $parcels = $this->parcels;

        return $this->mutate(static function (self $builder) use ($parcels): void {
            $builder->parcels = $parcels + 1;
        });
    }

    public function withoutParcels(): static
    {
        return $this->withParcels(0);
    }

    public function fromLabel(string $label)
    {
        return $this->mutate(static function (self $builder) use ($label): void {
            $builder->carrier = $label;
        });
    }

    public function build(): string
    {
        return sprintf('%s x%d', $this->carrier, $this->parcels);
    }
}

final class HandRolledBuilder implements \Pizgariu\ImmutableTestBuilder\Contract\BuilderInterface
{
    private string $label = 'crate';

    public static function create(): static
    {
        return new static();
    }

    public function withLabel(string $label): static
    {
        $clone = clone $this;
        $clone->label = $label;

        return $clone;
    }

    public function build(): string
    {
        return $this->label;
    }
}

final class CrateYardBuilder extends AbstractBuilder
{
    private string $label = 'crate';

    private function withScratch(string $label): static
    {
        $clone = clone $this;
        $clone->label = $label;

        return $clone;
    }

    public static function withTemplate(): self
    {
        return self::create();
    }

    public function build(): string
    {
        return $this->withScratch('marked')->label;
    }

    protected function seed(): void
    {
        $this->label = 'crate';
    }
}
