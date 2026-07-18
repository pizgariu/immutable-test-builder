<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Rule\Property;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Pizgariu\ImmutableTestBuilder\PHPStan\Rule\Property\WritableStateRule;

/**
 * @extends RuleTestCase<WritableStateRule>
 */
final class WritableStateRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new WritableStateRule();
    }

    public function testReportsExposedStaticAndReadonlyState(): void
    {
        $builder = 'Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Data\WritableState\CrateBuilder';

        $this->analyse([__DIR__ . '/../../data/writable-state.php'], [
            [
                sprintf('Property $exposed of builder %s must be private - builder state is sealed and modifiers write through closures that share class scope.', $builder),
                16,
            ],
            [
                sprintf('Property $slots of builder %s must be private - builder state is sealed and modifiers write through closures that share class scope.', $builder),
                18,
            ],
            [
                sprintf('Property $instances of builder %s must not be static - builder instances and their clones are independent, static state would leak across all of them.', $builder),
                20,
            ],
            [
                sprintf('Property $seal of builder %s must not be readonly - mutate() writes to the clone at runtime, readonly would make every modifier throw.', $builder),
                22,
            ],
        ]);
    }
}
