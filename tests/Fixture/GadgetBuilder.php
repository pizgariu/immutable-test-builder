<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\Fixture;

use DateTimeImmutable;
use Pizgariu\ImmutableTestBuilder\Implementation\AbstractBuilder;

/**
 * Its only property is a non-nullable object, so without*() has no empty value
 * the resolver can infer. Exercises that refusal.
 *
 * @extends AbstractBuilder<array<string, mixed>>
 */
final class GadgetBuilder extends AbstractBuilder
{
    private DateTimeImmutable $installedAt;

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        return ['installedAt' => $this->installedAt];
    }

    protected function seed(): void
    {
        $this->installedAt = new DateTimeImmutable('2000-01-01');
    }
}
