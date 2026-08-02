<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\Fixture;

/**
 * Exercises the map-form guard - one modifier mistypes its key, one targets the
 * base's private ingredient and one targets its shared static. All three must
 * fail loudly.
 *
 * @extends AbstractStationBuilder<string>
 */
final class StationBuilder extends AbstractStationBuilder
{
    private string $name;

    public function withMistypedName(string $name): static
    {
        return $this->mutate(['nane' => $name]);
    }

    public function forDesignation(string $designation): static
    {
        return $this->mutate(['designation' => $designation]);
    }

    public function withRegistry(string $registry): static
    {
        return $this->mutate(['registry' => $registry]);
    }

    public function build(): string
    {
        return sprintf('%s %s', $this->designation(), $this->name);
    }

    protected function seed(): void
    {
        $this->name = 'Anesidora';
    }
}
