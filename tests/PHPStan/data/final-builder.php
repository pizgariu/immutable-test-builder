<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Data\FinalBuilder;

use Pizgariu\ImmutableTestBuilder\AbstractBuilder;

/**
 * @extends AbstractBuilder<string>
 */
class OpenNameBuilder extends AbstractBuilder
{
    private string $name;

    protected function seed(): void
    {
        $this->name = 'Ripley';
    }

    public function build(): string
    {
        return $this->name;
    }
}

/**
 * @extends AbstractBuilder<string>
 */
final class SealedNameBuilder extends AbstractBuilder
{
    private string $name;

    protected function seed(): void
    {
        $this->name = 'Ripley';
    }

    public function build(): string
    {
        return $this->name;
    }
}

/**
 * @extends AbstractBuilder<string>
 */
abstract class NameBuilderBase extends AbstractBuilder
{
}

class PlainOldClass
{
}
