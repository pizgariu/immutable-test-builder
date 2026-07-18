<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Rule\Property;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Pizgariu\ImmutableTestBuilder\PHPStan\Rule\Property\PerfectDefaultPropertyRule;

/**
 * @extends RuleTestCase<PerfectDefaultPropertyRule>
 */
final class PerfectDefaultPropertyRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new PerfectDefaultPropertyRule();
    }

    public function testReportsPropertiesWithoutInlineDefaultOrSeedAssignment(): void
    {
        $builder = 'Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Data\PerfectDefault\ManifestBuilder';

        $this->analyse([__DIR__ . '/../../data/perfect-default.php'], [
            [
                sprintf('Property $forgotten of builder %s has no perfect default - give it an inline default or assign it directly in seed(); create() promises a builder that builds immediately.', $builder),
                18,
            ],
            [
                sprintf('Property $nullableForgotten of builder %s has no perfect default - give it an inline default or assign it directly in seed(); create() promises a builder that builds immediately.', $builder),
                20,
            ],
        ]);
    }
}
