<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\Fixture;

use Faker\Generator;
use Pizgariu\ImmutableTestBuilder\BaseBuilder;

/**
 * @extends BaseBuilder<string>
 */
final class PolishNameBuilder extends BaseBuilder
{
    private string $name;

    protected static function defaultLocale(): string
    {
        return 'pl_PL';
    }

    protected function seed(Generator $faker): void
    {
        $this->name = $faker->name();
    }

    public function build(): string
    {
        return $this->name;
    }
}
