<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\Fixture;

use Pizgariu\ImmutableTestBuilder\Implementation\AbstractBuilder;

/**
 * Ingredients declared as union types, which the derivation treats by one rule.
 *
 * A union is fine where the operation does not have to pick a member of it, and
 * refused where it would. with* only assigns, so it derives. without* would have
 * to choose between an empty array and an empty string, and including* would
 * append with []= against whatever the property happens to hold rather than what
 * it was declared as, so both refuse rather than work by luck.
 *
 * A nullable is NOT a union to reflection, which is why ?array derives normally.
 *
 * @method UnionBuilder withTags(array|string $tags)
 *
 * @extends AbstractBuilder<array<string, mixed>>
 */
final class UnionBuilder extends AbstractBuilder
{
    /** @var list<string>|string */
    private array|string $tags;

    private bool|string $flag;

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        return ['tags' => $this->tags, 'flag' => $this->flag];
    }

    protected function seed(): void
    {
        $this->tags = ['member'];
        $this->flag = false;
    }
}
