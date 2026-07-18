<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Rule\Method;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Pizgariu\ImmutableTestBuilder\PHPStan\Rule\Method\BuildReturnTypeRule;

/**
 * @extends RuleTestCase<BuildReturnTypeRule>
 */
final class BuildReturnTypeRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new BuildReturnTypeRule();
    }

    public function testReportsMissingMixedAndNullableReturnTypes(): void
    {
        $prefix = 'Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Data\BuildReturnType';
        $message = 'build() on builder %s\%s must declare a concrete non-nullable return type - an impossible state throws UnbuildableState, it never returns null or a half-thing.';

        $this->analyse([__DIR__ . '/../../data/build-return-type.php'], [
            [sprintf($message, $prefix, 'UntypedBuildBuilder'), 21],
            [sprintf($message, $prefix, 'NullableBuildBuilder'), 39],
            [sprintf($message, $prefix, 'MixedBuildBuilder'), 57],
            [sprintf($message, $prefix, 'UnionNullBuildBuilder'), 75],
        ]);
    }
}
