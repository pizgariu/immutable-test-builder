<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Data\SeedDiscipline;

use Pizgariu\ImmutableTestBuilder\Implementation\AbstractBuilder;

/**
 * @extends AbstractBuilder<string>
 */
final class PublicSeedBuilder extends AbstractBuilder
{
    private string $label = '';

    public function seed(): void
    {
        $this->label = 'crate';
    }

    public function build(): string
    {
        return $this->label;
    }
}

/**
 * @extends AbstractBuilder<string>
 */
final class ChattySeedBuilder extends AbstractBuilder
{
    private string $label = '';

    protected function seed(): void
    {
        $this->withLabel('crate');
        $this->build();
    }

    public function withLabel(string $label): static
    {
        return $this->mutate(static function (self $builder) use ($label): void {
            $builder->label = $label;
        });
    }

    public function build(): string
    {
        return $this->label;
    }
}

/**
 * @extends AbstractBuilder<string>
 */
final class CleanSeedBuilder extends AbstractBuilder
{
    private string $label = '';

    protected function seed(): void
    {
        $this->label = 'crate';
    }

    public function build(): string
    {
        return $this->label;
    }
}
