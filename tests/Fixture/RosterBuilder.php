<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\Fixture;

use Pizgariu\ImmutableTestBuilder\Contract\Attribute\NotMagic;
use Pizgariu\ImmutableTestBuilder\Contract\Attribute\Plural;
use Pizgariu\ImmutableTestBuilder\Implementation\AbstractBuilder;

/**
 * Exercises the derivation attributes - $people is the irregular plural of
 * 'person', and $checksum is sealed from magic.
 *
 * @method RosterBuilder includingPerson(string $person)
 * @method RosterBuilder excludingPerson(string $person)
 *
 * @extends AbstractBuilder<array<string, mixed>>
 */
final class RosterBuilder extends AbstractBuilder
{
    /** @var list<string> */
    #[Plural(of: 'person')]
    private array $people;

    #[NotMagic]
    private int $checksum;

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        return ['people' => $this->people, 'checksum' => $this->checksum];
    }

    protected function seed(): void
    {
        $this->people = ['Ripley'];
        $this->checksum = 0;
    }
}
