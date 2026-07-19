<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Data\WritableState;

use Pizgariu\ImmutableTestBuilder\Implementation\AbstractBuilder;

/**
 * @extends AbstractBuilder<string>
 */
final class CrateBuilder extends AbstractBuilder
{
    private string $label;

    public string $exposed = '';

    protected int $slots = 1;

    private static int $instances = 0;

    private readonly string $seal;

    protected function seed(): void
    {
        $this->label = 'crate';
        $this->seal = 'sealed';
    }

    public function build(): string
    {
        return $this->label;
    }
}

/**
 * @extends AbstractBuilder<string>
 */
abstract class CrateBuilderBase extends AbstractBuilder
{
    protected readonly string $blueprint;
}

final class NotABuilder
{
    public string $anything = '';
}
