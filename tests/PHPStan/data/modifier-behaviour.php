<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Data\ModifierBehaviour;

use Pizgariu\ImmutableTestBuilder\Implementation\AbstractBuilder;

/**
 * @extends AbstractBuilder<string>
 */
final class CargoBuilder extends AbstractBuilder
{
    /** @var list<string> */
    private array $tags = [];

    private ?string $owner = null;

    private int $weight = 0;

    private bool $sealed = false;

    private string $city = '';

    private string $street = '';

    protected function seed(): void
    {
        $this->owner = 'Ripley';
    }

    public function withoutOwner(string $owner): static
    {
        return $this->mutate(static function (self $builder): void {
            $builder->owner = null;
        });
    }

    public function asArchived(bool $flag): static
    {
        return $this->mutate(static function (self $builder) use ($flag): void {
            $builder->sealed = $flag;
        });
    }

    public function withName(): static
    {
        return $this->mutate(static function (self $builder): void {
            $builder->owner = 'crate';
        });
    }

    public function withoutWeight(): static
    {
        return $this->mutate(static function (self $builder): void {
            $builder->weight = 10;
        });
    }

    public function includingTag(string $tag): static
    {
        return $this->mutate(static function (self $builder) use ($tag): void {
            $builder->tags = [$tag];
        });
    }

    public function excludingTag(string $tag): static
    {
        return $this->mutate(static function (self $builder) use ($tag): void {
            $builder->tags[] = $tag;
        });
    }

    public function havingWeight(int $weight): static
    {
        return $this->mutate(static function (self $builder) use ($weight): void {
            $builder->weight = $weight;
        });
    }

    public function withOwner(string $owner): static
    {
        return $this->mutate(static function (self $builder) use ($owner): void {
            $builder->owner = $owner;
        });
    }

    public function withoutTags(): static
    {
        return $this->mutate(static function (self $builder): void {
            $builder->tags = [];
        });
    }

    public function includingHeavyTag(string $tag): static
    {
        return $this->mutate(static function (self $builder) use ($tag): void {
            $builder->tags[] = $tag;
        });
    }

    public function excludingLightTag(string $tag): static
    {
        return $this->mutate(static function (self $builder) use ($tag): void {
            $builder->tags = array_values(array_diff($builder->tags, [$tag]));
        });
    }

    public function havingAddress(string $city, string $street): static
    {
        return $this->mutate(static function (self $builder) use ($city, $street): void {
            $builder->city = $city;
            $builder->street = $street;
        });
    }

    public function asSealed(): static
    {
        return $this->mutate(static function (self $builder): void {
            $builder->sealed = true;
        });
    }

    public function build(): string
    {
        return sprintf('%s %s %s %d %d %d', $this->owner ?? '', $this->city, $this->street, $this->weight, (int) $this->sealed, count($this->tags));
    }
}

/**
 * @extends AbstractBuilder<string>
 */
final class ArrayCargoBuilder extends AbstractBuilder
{
    /** @var list<string> */
    private array $tags = [];

    private int $weight = 0;

    private string $city = '';

    private string $street = '';

    public function withWeight(int $weight): static
    {
        return $this->mutate(['weight' => $weight]);
    }

    public function withoutWeight(): static
    {
        return $this->mutate(['weight' => 10]);
    }

    public function withoutTags(): static
    {
        return $this->mutate(['tags' => []]);
    }

    public function includingTag(string $tag): static
    {
        return $this->mutate(['tags' => [$tag]]);
    }

    public function includingStripedTag(string $tag): static
    {
        return $this->mutate(['tags' => [...$this->tags, $tag]]);
    }

    public function havingWeight(int $weight): static
    {
        return $this->mutate(['weight' => $weight]);
    }

    public function havingAddress(string $city, string $street): static
    {
        return $this->mutate(['city' => $city, 'street' => $street]);
    }

    public function build(): string
    {
        return sprintf('%s %s %d %d', $this->city, $this->street, $this->weight, count($this->tags));
    }

    protected function seed(): void
    {
        $this->city = 'LV';
    }
}

final class FlagCargoBuilder extends AbstractBuilder
{
    private bool $sealed = false;

    private ?bool $flagged = null;

    private string $band = '';

    public function asSealed(bool $sealed = true): static
    {
        return $this->mutate(['sealed' => $sealed]);
    }

    public function asFlagged(?bool $flagged = null): static
    {
        return $this->mutate(['flagged' => $flagged]);
    }

    public function asDocked(bool $flag): static
    {
        return $this->mutate(['sealed' => $flag]);
    }

    public function asBanded(string $band = 'crate'): static
    {
        return $this->mutate(['band' => $band]);
    }

    public function build(): string
    {
        return $this->band;
    }

    protected function seed(): void
    {
        $this->band = 'plain';
    }
}
