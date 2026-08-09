<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Rule\Class;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Pizgariu\ImmutableTestBuilder\PHPStan\Rule\Class\BuiltTypeCoverageRule;

/**
 * The builders this file expects NOTHING from carry as much weight as the ones it
 * expects errors from. UnpromisedWidgetBuilder is LeakyWidgetBuilder without the
 * attribute, so its silence proves the promise is read rather than assumed.
 * InheritedPromiseBuilder proves the promise does not travel down from a base.
 * ReplacingWidgetBuilder is readonly and hand-rolled, so its silence proves the
 * writability question is only asked of builders that write through mutate().
 *
 * @extends RuleTestCase<BuiltTypeCoverageRule>
 */
final class BuiltTypeCoverageRuleTest extends RuleTestCase
{
    private const string NAMESPACE = 'Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Data\BuiltTypeCoverage';

    private const string FIXTURE = 'Pizgariu\ImmutableTestBuilder\Tests\Fixture';

    protected function getRule(): Rule
    {
        return new BuiltTypeCoverageRule($this->createReflectionProvider());
    }

    public function testReportsRequiredIngredientsAPromisedBuilderCannotWrite(): void
    {
        $this->analyse([__DIR__ . '/../../data/built-type-coverage.php'], [
            [self::uncovered('LeakyWidgetBuilder', 'quantity', 'Widget'), 41],
            [self::uncovered('BeaconBuilder', 'registry', 'Beacon'), 87],
            [self::uncovered('BeaconBuilder', 'commissioned', 'Beacon'), 87],
            [self::uncovered('HandRolledWidgetBuilder', 'quantity', 'Widget'), 111],
            [self::unusable('ScalarBuilder', 'build() returns string, which names no single constructed class'), 130],
            [self::unusable('DerelictBuilder', sprintf('%s\Derelict declares no constructor, so it names no required ingredients', self::FIXTURE)), 149],
            [self::unusable('EitherBuilder', sprintf('build() returns %1$s\Beacon|%1$s\Widget, which names no single constructed class', self::FIXTURE)), 166],
            [
                sprintf(
                    'Builder %s\AbstractPromisingBuilder declares #[CoversBuiltType] on an abstract base, where the promise is never checked, because it is not inherited. Move the attribute onto each builder that is finished.',
                    self::NAMESPACE,
                ),
                210,
            ],
            [self::unusable('ReflectingBuilder', 'the constructor of ReflectionMethod declares more than one signature, so it names no single set of required ingredients'), 239],
            [self::unusable('SelfReturningBuilder', 'build() hands back the builder itself, so it names no built type'), 261],
        ]);
    }

    private static function uncovered(string $builder, string $property, string $built): string
    {
        return sprintf(
            'Builder %s\%s declares #[CoversBuiltType] but its own scope cannot write $%s, which %s\%s requires, so nothing here varies that ingredient. Declare a private $%s, widen a base-declared one to protected, or drop the attribute if this builder fixes the value on purpose.',
            self::NAMESPACE,
            $builder,
            $property,
            self::FIXTURE,
            $built,
            $property,
        );
    }

    private static function unusable(string $builder, string $reason): string
    {
        return sprintf(
            'Builder %s\%s declares #[CoversBuiltType] and the promise cannot be checked, because %s. Point build() at the class this builder produces, or drop the attribute.',
            self::NAMESPACE,
            $builder,
            $reason,
        );
    }
}
