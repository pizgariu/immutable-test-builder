<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\PHPStan\Rule\Method;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use Pizgariu\ImmutableTestBuilder\Contract\Enum\KernelMethod;
use Pizgariu\ImmutableTestBuilder\PHPStan\ConcreteBuilder;

/**
 * A modifier is a one-liner: a static-returning single statement delegating
 * to mutate(). The clone-and-write lives in the kernel, so this shape is the
 * whole immutability proof a concrete builder needs.
 *
 * @implements Rule<InClassMethodNode>
 */
final class ModifierDelegatesToMutateRule implements Rule
{
    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }

    /**
     * @throws ShouldNotHappenException
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $class = ConcreteBuilder::kernelFromScope($scope);

        if (null === $class) {
            return [];
        }

        $method = $node->getOriginalNode();
        $name = ConcreteBuilder::declaredModifierName($method);

        if (null === $name) {
            return [];
        }

        $errors = [];

        if (!$this->declaresStaticReturnType($method->returnType)) {
            $errors[] = RuleErrorBuilder::message(sprintf(
                'Modifier %s() on builder %s must declare a static return type - every modifier hands back a new builder instance.',
                $name,
                $class->getDisplayName(),
            ))
                ->identifier('immutableTestBuilder.modifierReturnType')
                ->build()
            ;
        }

        if (null !== $method->stmts && !$this->isSingleReturnThroughMutate($method->stmts)) {
            $errors[] = RuleErrorBuilder::message(sprintf(
                'Modifier %s() on builder %s must be a single return through $this->mutate(...) - the clone-and-write lives in the kernel, not in modifiers.',
                $name,
                $class->getDisplayName(),
            ))
                ->identifier('immutableTestBuilder.modifierBody')
                ->build()
            ;
        }

        return $errors;
    }

    private function declaresStaticReturnType(?Node $returnType): bool
    {
        if ($returnType instanceof Identifier) {
            return 'static' === $returnType->toLowerString();
        }

        if ($returnType instanceof Name) {
            return 'static' === strtolower($returnType->toString());
        }

        return false;
    }

    /**
     * @param array<Node\Stmt> $statements
     */
    private function isSingleReturnThroughMutate(array $statements): bool
    {
        if (1 !== count($statements)) {
            return false;
        }

        $statement = $statements[0];

        if (!$statement instanceof Return_ || !$statement->expr instanceof MethodCall) {
            return false;
        }

        $call = $statement->expr;

        return $call->var instanceof Variable && is_string($call->var->name) && 'this' === $call->var->name && $call->name instanceof Identifier && KernelMethod::Mutate->value === $call->name->toString();
    }
}
