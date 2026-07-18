<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Rule\Method;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Pizgariu\ImmutableTestBuilder\PHPStan\Rule\Method\ModifierDelegatesToMutateRule;

/**
 * @extends RuleTestCase<ModifierDelegatesToMutateRule>
 */
final class ModifierDelegatesToMutateRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ModifierDelegatesToMutateRule();
    }

    public function testReportsModifiersThatDoNotDelegateToMutate(): void
    {
        $builder = 'Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Data\ModifierDelegates\ShipmentBuilder';

        $this->analyse([__DIR__ . '/../../data/modifier-delegates.php'], [
            [
                sprintf('Modifier withParcels() on builder %s must declare a static return type - every modifier hands back a new builder instance.', $builder),
                30,
            ],
            [
                sprintf('Modifier asExpress() on builder %s must be a single return through $this->mutate(...) - the clone-and-write lives in the kernel, not in modifiers.', $builder),
                37,
            ],
            [
                sprintf('Modifier withoutParcels() on builder %s must be a single return through $this->mutate(...) - the clone-and-write lives in the kernel, not in modifiers.', $builder),
                46,
            ],
            [
                sprintf('Modifier fromLabel() on builder %s must declare a static return type - every modifier hands back a new builder instance.', $builder),
                51,
            ],
        ]);
    }
}
