<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\PHPStan\Rule\Class;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use Pizgariu\ImmutableTestBuilder\Contract\BuilderInterface;

/**
 * A concrete builder is a leaf - extension points belong in an abstract base, never in a buildable class.
 *
 * @implements Rule<InClassNode>
 */
final class FinalBuilderRule implements Rule
{
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @throws ShouldNotHappenException
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $class = $node->getClassReflection();

        if ($class->isInterface() || $class->isTrait() || $class->isEnum()) {
            return [];
        }

        if ($class->isAbstract() || $class->isAnonymous() || $class->isFinal()) {
            return [];
        }

        if (!$class->implementsInterface(BuilderInterface::class)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'Builder %s must be final - a concrete builder is a leaf. Put extension points in an abstract base instead.',
                $class->getDisplayName(),
            ))
                ->identifier('immutableTestBuilder.builderNotFinal')
                ->build(),
        ];
    }
}
