<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\PHPStan\Rule\Property;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignOp;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Pizgariu\ImmutableTestBuilder\AbstractBuilder;
use Pizgariu\ImmutableTestBuilder\Contract\BuilderInterface;

/**
 * The perfect default as a per-property obligation: every non-static
 * property of a concrete builder either carries an inline default or is
 * assigned directly in seed(). Deliberate limit: only direct
 * $this->property assignments inside seed() count - a builder that seeds
 * through helper methods opts out of this rule's protection.
 *
 * @implements Rule<InClassNode>
 */
final class PerfectDefaultPropertyRule implements Rule
{
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $class = $node->getClassReflection();

        if ($class->isInterface() || $class->isTrait() || $class->isEnum()) {
            return [];
        }

        if ($class->isAbstract() || $class->isAnonymous()) {
            return [];
        }

        if (!$class->implementsInterface(BuilderInterface::class) || !$class->isSubclassOf(AbstractBuilder::class)) {
            return [];
        }

        $classNode = $node->getOriginalNode();

        if (!$classNode instanceof Class_) {
            return [];
        }

        $seeded = $this->propertiesAssignedInSeed($classNode);
        $errors = [];

        foreach ($classNode->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }

            foreach ($property->props as $declaration) {
                if (null !== $declaration->default) {
                    continue;
                }

                $name = $declaration->name->toString();

                if (isset($seeded[$name])) {
                    continue;
                }

                $errors[] = RuleErrorBuilder::message(sprintf(
                    'Property $%s of builder %s has no perfect default - give it an inline default or assign it directly in seed(). create() promises a builder that builds immediately.',
                    $name,
                    $class->getDisplayName(),
                ))->identifier('immutableTestBuilder.perfectDefault')->line($declaration->getStartLine())->build();
            }
        }

        return $errors;
    }

    /**
     * @return array<string, true>
     */
    private function propertiesAssignedInSeed(Class_ $classNode): array
    {
        $seed = $classNode->getMethod('seed');

        if (null === $seed || null === $seed->stmts) {
            return [];
        }

        $finder = new NodeFinder();
        $assigned = [];

        foreach ([Assign::class, AssignOp::class] as $assignmentKind) {
            foreach ($finder->findInstanceOf($seed->stmts, $assignmentKind) as $assignment) {
                $target = $assignment->var;

                if (!$target instanceof PropertyFetch) {
                    continue;
                }

                if (!$target->var instanceof Variable || !is_string($target->var->name) || 'this' !== $target->var->name) {
                    continue;
                }

                if ($target->name instanceof Identifier) {
                    $assigned[$target->name->toString()] = true;
                }
            }
        }

        return $assigned;
    }
}
