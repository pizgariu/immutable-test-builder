<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\PHPStan\Rule\Method;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Pizgariu\ImmutableTestBuilder\Contract\BuilderInterface;

/**
 * The mutation closure receives the clone as its parameter and has no
 * business holding $this: a non-static closure keeps $this bound to the
 * ORIGINAL builder, and a single $this-> write inside it would mutate the
 * trunk behind mutate()'s back.
 *
 * @implements Rule<MethodCall>
 */
final class StaticMutationClosureRule implements Rule
{
    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $class = $scope->getClassReflection();

        if (null === $class || !$class->implementsInterface(BuilderInterface::class)) {
            return [];
        }

        if (!$node->var instanceof Variable || !is_string($node->var->name) || 'this' !== $node->var->name) {
            return [];
        }

        if (!$node->name instanceof Identifier || 'mutate' !== $node->name->toString()) {
            return [];
        }

        if ($node->isFirstClassCallable()) {
            return [];
        }

        $arguments = $node->getArgs();

        if (!isset($arguments[0])) {
            return [];
        }

        $mutation = $arguments[0]->value;

        $isNonStaticClosure = ($mutation instanceof Closure && !$mutation->static)
            || ($mutation instanceof ArrowFunction && !$mutation->static);

        if (!$isNonStaticClosure) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'The mutation closure passed to mutate() in %s must be static - a non-static closure keeps $this bound to the original builder, and a single $this-> write inside it would mutate the trunk behind mutate()\'s back.',
                $class->getDisplayName(),
            ))
                ->identifier('immutableTestBuilder.mutationClosureNotStatic')
                ->build(),
        ];
    }
}
