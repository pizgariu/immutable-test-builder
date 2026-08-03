<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\Fixture;

use Pizgariu\ImmutableTestBuilder\Implementation\AbstractBuilder;

/**
 * A builder the size of a real one, for measuring at the scale a suite works at.
 *
 * The fixtures beside it hold a handful of ingredients, which is enough to prove
 * behaviour and useless for pricing it. Twenty ingredients is where a builder for
 * a domain entity actually lands, and the second half of them carry handwritten
 * modifiers so a chain of derived calls can be read against a chain of declared
 * ones on the same object rather than across two different fixtures.
 *
 * seed() stays deliberately cheap. What a project puts in its own seed dominates
 * everything the kernel does, so leaving expensive work in here would measure the
 * work rather than the kernel.
 *
 * @method ColonyBuilder withDesignation(string $designation)
 * @method ColonyBuilder withSector(string $sector)
 * @method ColonyBuilder withProcessors(int $processors)
 * @method ColonyBuilder withAtmosphere(float $atmosphere)
 * @method ColonyBuilder withOperator(?string $operator)
 * @method ColonyBuilder asTerraformed(bool $terraformed = true)
 * @method ColonyBuilder asQuarantined(bool $quarantined = true)
 * @method ColonyBuilder withoutOperator()
 * @method ColonyBuilder includingModule(string $module)
 * @method ColonyBuilder excludingModule(string $module)
 *
 * @extends AbstractBuilder<array<string, mixed>>
 */
final class ColonyBuilder extends AbstractBuilder
{
    private string $designation;

    private string $sector;

    private int $processors;

    private float $atmosphere;

    private ?string $operator;

    private bool $terraformed;

    private bool $quarantined;

    /** @var list<string> */
    private array $modules;

    private string $registry;

    private int $population;

    private string $charter;

    private string $callsign;

    private int $founded;

    private float $gravity;

    private ?string $liaison;

    private bool $selfSufficient;

    private bool $evacuated;

    /** @var list<string> */
    private array $permits;

    private string $network;

    private int $berths;

    public function withRegistry(string $registry): static
    {
        return $this->mutate(['registry' => $registry]);
    }

    public function withPopulation(int $population): static
    {
        return $this->mutate(['population' => $population]);
    }

    public function withCharter(string $charter): static
    {
        return $this->mutate(['charter' => $charter]);
    }

    public function withCallsign(string $callsign): static
    {
        return $this->mutate(['callsign' => $callsign]);
    }

    public function withFounded(int $founded): static
    {
        return $this->mutate(['founded' => $founded]);
    }

    public function withGravity(float $gravity): static
    {
        return $this->mutate(['gravity' => $gravity]);
    }

    public function withoutLiaison(): static
    {
        return $this->mutate(['liaison' => null]);
    }

    public function asSelfSufficient(bool $selfSufficient = true): static
    {
        return $this->mutate(['selfSufficient' => $selfSufficient]);
    }

    public function asEvacuated(bool $evacuated = true): static
    {
        return $this->mutate(['evacuated' => $evacuated]);
    }

    public function includingPermit(string $permit): static
    {
        return $this->mutate(static function (self $clone) use ($permit): void {
            $clone->permits[] = $permit;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        return [
            'designation' => $this->designation,
            'sector' => $this->sector,
            'processors' => $this->processors,
            'atmosphere' => $this->atmosphere,
            'operator' => $this->operator,
            'terraformed' => $this->terraformed,
            'quarantined' => $this->quarantined,
            'modules' => $this->modules,
            'registry' => $this->registry,
            'population' => $this->population,
            'charter' => $this->charter,
            'callsign' => $this->callsign,
            'founded' => $this->founded,
            'gravity' => $this->gravity,
            'liaison' => $this->liaison,
            'selfSufficient' => $this->selfSufficient,
            'evacuated' => $this->evacuated,
            'permits' => $this->permits,
            'network' => $this->network,
            'berths' => $this->berths,
        ];
    }

    protected function seed(): void
    {
        $this->designation = 'Hadley';
        $this->sector = 'LV-426';
        $this->processors = 2;
        $this->atmosphere = 0.7;
        $this->operator = 'Weyland-Yutani';
        $this->terraformed = false;
        $this->quarantined = false;
        $this->modules = ['operations'];
        $this->registry = 'HH-0001';
        $this->population = 158;
        $this->charter = 'colonial';
        $this->callsign = 'Hadleys Hope';
        $this->founded = 2179;
        $this->gravity = 0.9;
        $this->liaison = 'Burke';
        $this->selfSufficient = false;
        $this->evacuated = false;
        $this->permits = ['atmospheric'];
        $this->network = 'colonial-net';
        $this->berths = 12;
    }
}
