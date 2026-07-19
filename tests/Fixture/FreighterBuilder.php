<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\Fixture;

use Pizgariu\ImmutableTestBuilder\Implementation\AbstractBuilder;

/**
 * Declares only state, seed() and build() - every modifier exercised by the
 * magic tests is derived by the kernel.
 *
 * @extends AbstractBuilder<array<string, mixed>>
 */
final class FreighterBuilder extends AbstractBuilder
{
    private ?string $pilot;

    private string $callsign;

    private int $cargo;

    private float $fuel;

    private bool $armed;

    /** @var list<string> */
    private array $decals;

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        return [
            'pilot' => $this->pilot,
            'callsign' => $this->callsign,
            'cargo' => $this->cargo,
            'fuel' => $this->fuel,
            'armed' => $this->armed,
            'decals' => $this->decals,
        ];
    }

    protected function seed(): void
    {
        $this->pilot = 'Ripley';
        $this->callsign = sprintf('Rocinante-%04d', random_int(1, 9999));
        $this->cargo = random_int(1, 100);
        $this->fuel = 1.5;
        $this->armed = false;
        $this->decals = ['stripe'];
    }
}
