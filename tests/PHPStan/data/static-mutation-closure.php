<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Data\StaticMutationClosure;

use Pizgariu\ImmutableTestBuilder\AbstractBuilder;

/**
 * @extends AbstractBuilder<string>
 */
final class LabelBuilder extends AbstractBuilder
{
    private string $label = '';

    protected function seed(): void
    {
        $this->label = 'crate';
    }

    public function withLabel(string $label): static
    {
        return $this->mutate(static function (self $builder) use ($label): void {
            $builder->label = $label;
        });
    }

    public function withLooseLabel(string $label): static
    {
        return $this->mutate(function (self $builder) use ($label): void {
            $builder->label = $label;
        });
    }

    public function asBlank(): static
    {
        return $this->mutate(static fn (self $builder) => $builder->label = '');
    }

    public function asLooseBlank(): static
    {
        return $this->mutate(fn (self $builder) => $builder->label = '');
    }

    public function build(): string
    {
        return $this->label;
    }
}
