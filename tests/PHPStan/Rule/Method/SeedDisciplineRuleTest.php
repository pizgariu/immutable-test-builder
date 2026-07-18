<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Rule\Method;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Pizgariu\ImmutableTestBuilder\PHPStan\Rule\Method\SeedDisciplineRule;

/**
 * @extends RuleTestCase<SeedDisciplineRule>
 */
final class SeedDisciplineRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new SeedDisciplineRule();
    }

    public function testReportsPublicSeedAndCallsIntoTheDsl(): void
    {
        $this->analyse([__DIR__ . '/../../data/seed-discipline.php'], [
            [
                'seed() on builder Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Data\SeedDiscipline\PublicSeedBuilder must stay protected - a public seed() can be re-run on a live builder, and re-seeding is mutation through the back door.',
                16,
            ],
            [
                'seed() on builder Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Data\SeedDiscipline\ChattySeedBuilder calls withLabel() - a modifier returns a new clone that seed() throws away; assign the property directly instead.',
                36,
            ],
            [
                'seed() on builder Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Data\SeedDiscipline\ChattySeedBuilder calls build() - the builder is not complete while it is being seeded.',
                37,
            ],
        ]);
    }
}
