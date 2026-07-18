<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\PHPStan\Rule\Property;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use Pizgariu\ImmutableTestBuilder\PHPStan\ConcreteBuilder;

/**
 * Builder state is sealed and writable: private (modifiers write through
 * closures sharing class scope), per-instance and never readonly, because
 * mutate() writes to the clone at runtime. Abstract bases are exempt - they
 * may hold immutable configuration, like a memoized project-wide generator.
 *
 * @implements Rule<ClassPropertyNode>
 */
final class WritableStateRule implements Rule
{
    public function getNodeType(): string
    {
        return ClassPropertyNode::class;
    }

    /**
     * @throws ShouldNotHappenException
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $class = ConcreteBuilder::fromScope($scope);

        if (null === $class) {
            return [];
        }

        $errors = [];

        if (!$node->isPrivate()) {
            $errors[] = RuleErrorBuilder::message(sprintf(
                'Property $%s of builder %s must be private - builder state is sealed and modifiers write through closures that share class scope.',
                $node->getName(),
                $class->getDisplayName(),
            ))
                ->identifier('immutableTestBuilder.propertyVisibility')
                ->build()
            ;
        }

        if ($node->isStatic()) {
            $errors[] = RuleErrorBuilder::message(sprintf(
                'Property $%s of builder %s must not be static - builder instances and their clones are independent, static state would leak across all of them.',
                $node->getName(),
                $class->getDisplayName(),
            ))
                ->identifier('immutableTestBuilder.staticProperty')
                ->build()
            ;
        }

        if ($node->isReadonly()) {
            $errors[] = RuleErrorBuilder::message(sprintf(
                'Property $%s of builder %s must not be readonly - mutate() writes to the clone at runtime, readonly would make every modifier throw.',
                $node->getName(),
                $class->getDisplayName(),
            ))
                ->identifier('immutableTestBuilder.readonlyProperty')
                ->build()
            ;
        }

        return $errors;
    }
}
