<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\Fixture;

use Pizgariu\ImmutableTestBuilder\Implementation\AbstractBuilder;

/**
 * Declares only state, seed() and build() - every modifier exercised by the
 * magic tests is derived by the kernel.
 *
 * @method FreighterBuilder withCallsign(string $callsign)
 * @method FreighterBuilder withoutArmed()
 * @method FreighterBuilder asArmed(bool $armed = true)
 * @method FreighterBuilder asMothballed(?bool $mothballed = true)
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

    private ?bool $mothballed;

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
            'mothballed' => $this->mothballed,
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
        $this->mothballed = false;
        $this->decals = ['stripe'];
    }
}
