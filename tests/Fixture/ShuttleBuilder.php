<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\Fixture;

use DateTimeImmutable;
use Pizgariu\ImmutableTestBuilder\Implementation\AbstractBuilder;

/**
 * Deliberately carries NO @method tags - this builder is the dogfood guard
 * for MagicModifierMethodsExtension. Every magic call on it in the suite is
 * typed by the extension alone, so blinding or unregistering the extension
 * turns the level max analysis red. It also pins two contracts - $cargo is
 * a nullable collection seeded null, and $departedAt is an immutable object
 * ingredient the shallow clone shares with the trunk.
 *
 * @extends AbstractBuilder<array<string, mixed>>
 */
final class ShuttleBuilder extends AbstractBuilder
{
    private string $name;

    private DateTimeImmutable $departedAt;

    private bool $docked;

    /** @var list<string>|null */
    private ?array $cargo;

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        return [
            'name' => $this->name,
            'departedAt' => $this->departedAt,
            'docked' => $this->docked,
            'cargo' => $this->cargo,
        ];
    }

    protected function seed(): void
    {
        $this->name = sprintf('Narcissus-%04d', random_int(1, 9999));
        $this->departedAt = new DateTimeImmutable('2122-06-01');
        $this->docked = true;
        $this->cargo = null;
    }
}
