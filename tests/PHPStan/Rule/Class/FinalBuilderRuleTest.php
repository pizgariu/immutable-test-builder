<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Rule\Class;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Pizgariu\ImmutableTestBuilder\PHPStan\Rule\Class\FinalBuilderRule;

/**
 * @extends RuleTestCase<FinalBuilderRule>
 */
final class FinalBuilderRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new FinalBuilderRule();
    }

    public function testReportsOnlyOpenConcreteBuilders(): void
    {
        $this->analyse([__DIR__ . '/../../data/final-builder.php'], [
            [
                'Builder Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Data\FinalBuilder\OpenNameBuilder must be final - a concrete builder is a leaf; put extension points in an abstract base instead.',
                12,
            ],
        ]);
    }
}
