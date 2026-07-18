<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Data\PerfectDefault;

use Pizgariu\ImmutableTestBuilder\AbstractBuilder;

/**
 * @extends AbstractBuilder<string>
 */
final class ManifestBuilder extends AbstractBuilder
{
    private string $seeded;

    private int $defaulted = 1;

    private string $forgotten;

    private ?string $nullableForgotten;

    protected function seed(): void
    {
        $this->seeded = 'crate';
    }

    public function build(): string
    {
        return sprintf('%s %d', $this->seeded, $this->defaulted);
    }
}
