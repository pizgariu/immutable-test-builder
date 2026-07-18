<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Rule\Method;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Pizgariu\ImmutableTestBuilder\PHPStan\Rule\Method\ModifierBehaviourRule;

/**
 * @extends RuleTestCase<ModifierBehaviourRule>
 */
final class ModifierBehaviourRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ModifierBehaviourRule();
    }

    public function testReportsModifiersBreakingTheirPrefixPromise(): void
    {
        $builder = 'Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Data\ModifierBehaviour\CargoBuilder';
        $arrayBuilder = 'Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Data\ModifierBehaviour\ArrayCargoBuilder';

        $this->analyse([__DIR__ . '/../../data/modifier-behaviour.php'], [
            [
                sprintf('withoutOwner() on builder %s must not take parameters - a without* or as* modifier names the entire change in its method name.', $builder),
                32,
            ],
            [
                sprintf('asArchived() on builder %s must not take parameters - a without* or as* modifier names the entire change in its method name.', $builder),
                39,
            ],
            [
                sprintf('withName() on builder %s must take a parameter - with* feeds the builder outside data. A parameterless modifier is an as*.', $builder),
                46,
            ],
            [
                sprintf('withoutWeight() on builder %s assigns a non-empty value - without* promises emptying or nullifying. A real value makes it a with* wearing a mask.', $builder),
                53,
            ],
            [
                sprintf('includingTag() on builder %s never appends - including* promises extending a collection with []=. Replacing the whole collection is a with*.', $builder),
                60,
            ],
            [
                sprintf('excludingTag() on builder %s appends with []= - excluding* promises shrinking a collection, not growing it.', $builder),
                67,
            ],
            [
                sprintf('havingWeight() on builder %s mutates a single property - having* is for one inseparable multi-property concept. A single write is a with*.', $builder),
                74,
            ],
            [
                sprintf('withoutWeight() on builder %s assigns a non-empty value - without* promises emptying or nullifying. A real value makes it a with* wearing a mask.', $arrayBuilder),
                149,
            ],
            [
                sprintf('includingTag() on builder %s never appends - including* promises extending a collection with []=. Replacing the whole collection is a with*.', $arrayBuilder),
                159,
            ],
            [
                sprintf('havingWeight() on builder %s mutates a single property - having* is for one inseparable multi-property concept. A single write is a with*.', $arrayBuilder),
                169,
            ],
        ]);
    }
}
