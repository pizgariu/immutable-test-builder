<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\Fixture;

use Faker\Generator;
use Pizgariu\ImmutableTestBuilder\BaseBuilder;
use Pizgariu\ImmutableTestBuilder\Exception\UnbuildableState;

/**
 * @extends BaseBuilder<Spaceship>
 */
final class SpaceshipBuilder extends BaseBuilder
{
    private string $name;

    private int $fuel;

    private int $crew;

    private bool $launched;

    protected function seed(Generator $faker): void
    {
        $this->name = $faker->name();
        $this->fuel = $faker->numberBetween(1, 100);
        $this->crew = $faker->numberBetween(1, 12);
        $this->launched = false;
    }

    public function withName(string $name): static
    {
        return $this->mutate(static function (self $builder) use ($name): void {
            $builder->name = $name;
        });
    }

    public function withCrew(int $crew): static
    {
        return $this->mutate(static function (self $builder) use ($crew): void {
            $builder->crew = $crew;
        });
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
}
