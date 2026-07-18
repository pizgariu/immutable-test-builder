<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Data\BuildReturnType;

use Pizgariu\ImmutableTestBuilder\AbstractBuilder;

/**
 * @extends AbstractBuilder<string>
 */
final class UntypedBuildBuilder extends AbstractBuilder
{
    private string $label = '';

    protected function seed(): void
    {
        $this->label = 'crate';
    }

    public function build()
    {
        return $this->label;
    }
}

/**
 * @extends AbstractBuilder<string>
 */
final class NullableBuildBuilder extends AbstractBuilder
{
    private string $label = '';

    protected function seed(): void
    {
        $this->label = 'crate';
    }

    public function build(): ?string
    {
        return $this->label;
    }
}

/**
 * @extends AbstractBuilder<string>
 */
final class MixedBuildBuilder extends AbstractBuilder
{
    private string $label = '';

    protected function seed(): void
    {
        $this->label = 'crate';
    }

    public function build(): mixed
    {
        return $this->label;
    }
}

/**
 * @extends AbstractBuilder<string>
 */
final class UnionNullBuildBuilder extends AbstractBuilder
{
    private string $label = '';

    protected function seed(): void
    {
        $this->label = 'crate';
    }

    public function build(): string|null
    {
        return $this->label;
    }
}

/**
 * @extends AbstractBuilder<string>
 */
final class SoundBuildBuilder extends AbstractBuilder
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
