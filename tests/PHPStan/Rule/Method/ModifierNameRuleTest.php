<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Rule\Method;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Pizgariu\ImmutableTestBuilder\PHPStan\Rule\Method\ModifierNameRule;

/**
 * @extends RuleTestCase<ModifierNameRule>
 */
final class ModifierNameRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ModifierNameRule();
    }

    public function testReportsPublicMethodsOutsideTheDsl(): void
    {
        $builder = 'Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Data\ModifierName\AccountBuilder';

        $this->analyse([__DIR__ . '/../../data/modifier-name.php'], [
            [
                sprintf('setOwner() on builder %s starts with set* - set* promises an in-place write, add* hides whether the collection is replaced, make* says nothing. Modifiers are with*, without*, as*, from*, for*, including*, excluding* or having* and always return a new instance.', $builder),
                30,
            ],
            [
                sprintf('makeInactive() on builder %s starts with make* - set* promises an in-place write, add* hides whether the collection is replaced, make* says nothing. Modifiers are with*, without*, as*, from*, for*, including*, excluding* or having* and always return a new instance.', $builder),
                37,
            ],
            [
                sprintf('addOwner() on builder %s starts with add* - set* promises an in-place write, add* hides whether the collection is replaced, make* says nothing. Modifiers are with*, without*, as*, from*, for*, including*, excluding* or having* and always return a new instance.', $builder),
                44,
            ],
            [
                sprintf('Public method normalizedOwner() on builder %s is outside the DSL - the public surface is build() and modifiers prefixed with*, without*, as*, from*, for*, including*, excluding* or having*.', $builder),
                49,
            ],
            [
                sprintf('Public static method fromScratch() on builder %s is outside the DSL - the only static surface is create().', $builder),
                54,
            ],
        ]);
    }
}
