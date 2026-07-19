<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\Fixture;

use Pizgariu\ImmutableTestBuilder\Exception\UnbuildableState;
use Pizgariu\ImmutableTestBuilder\Implementation\AbstractBuilder;

/**
 * @extends AbstractBuilder<Spaceship>
 */
final class SpaceshipBuilder extends AbstractBuilder
{
    private string $name;

    private int $fuel;

    private int $crew;

    private bool $launched;

    public function withName(string $name): static
    {
        return $this->mutate(static function (self $builder) use ($name): void {
            $builder->name = $name;
        });
    }

    public function withCrew(int $crew): static
    {
        return $this->mutate(['crew' => $crew]);
    }

    public function asLaunched(): static
    {
        return $this->mutate(static function (self $builder): void {
            $builder->launched = true;
        });
    }

    public function withoutFuel(): static
    {
        return $this->mutate(static function (self $builder): void {
            $builder->fuel = 0;
        });
    }

    /**
     * @throws UnbuildableState when the ship is launched with an empty fuel tank
     */
    public function build(): Spaceship
    {
        if ($this->launched && 0 === $this->fuel) {
            throw UnbuildableState::contradiction(
                self::class,
                'the ship is launched but its fuel tank is empty',
                'Drop withoutFuel() or skip asLaunched().',
            );
        }

        return new Spaceship($this->name, $this->fuel, $this->crew, $this->launched);
    }

    protected function seed(): void
    {
        $this->name = sprintf('Nostromo-%04d', random_int(1, 9999));
        $this->fuel = random_int(1, 100);
        $this->crew = random_int(1, 12);
        $this->launched = false;
    }
}
