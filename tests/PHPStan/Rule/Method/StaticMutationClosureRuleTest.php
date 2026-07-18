<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Rule\Method;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Pizgariu\ImmutableTestBuilder\PHPStan\Rule\Method\StaticMutationClosureRule;

/**
 * @extends RuleTestCase<StaticMutationClosureRule>
 */
final class StaticMutationClosureRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new StaticMutationClosureRule();
    }

    public function testReportsNonStaticMutationClosures(): void
    {
        $message = 'The mutation closure passed to mutate() in Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Data\StaticMutationClosure\LabelBuilder must be static - a non-static closure keeps $this bound to the original builder, and a single $this-> write inside it would mutate the trunk behind mutate()\'s back.';

        $this->analyse([__DIR__ . '/../../data/static-mutation-closure.php'], [
            [$message, 30],
            [$message, 42],
        ]);
    }
}
