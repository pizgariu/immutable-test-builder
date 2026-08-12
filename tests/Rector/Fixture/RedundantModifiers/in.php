<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\Rector\Fixture\RedundantModifiers;

use Pizgariu\ImmutableTestBuilder\Implementation\AbstractBuilder;

final class RedundantBuilder extends AbstractBuilder
{
    private string $name;

    private bool $active;

    private ?string $email;

    private string $note;

    public function withName(string $name): static
    {
        return $this->mutate(['name' => $name]);
    }

    public function asActive(): static
    {
        return $this->mutate(['active' => true]);
    }

    public function withoutEmail(): static
    {
        return $this->mutate(['email' => null]);
    }

    public function withNote(string $note): static
    {
        return $this->mutate(['note' => strtoupper($note)]);
    }

    public function build(): string
    {
        return $this->name;
    }

    protected function seed(): void
    {
        $this->name = 'n';
        $this->active = false;
        $this->email = null;
        $this->note = '';
    }
}
