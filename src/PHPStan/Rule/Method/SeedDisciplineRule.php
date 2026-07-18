<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\PHPStan\Rule\Method;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Pizgariu\ImmutableTestBuilder\Contract\BuilderInterface;
use Pizgariu\ImmutableTestBuilder\Prefix;

/**
 * seed() fills the perfect default and nothing else: it stays protected
 * (a public seed() could re-run on a live builder, which is mutation through
 * the back door), it never calls a modifier (the returned clone would be
 * thrown away) and it never calls build() (the builder is not complete yet).
 *
 * @implements Rule<InClassMethodNode>
 */
final class SeedDisciplineRule implements Rule
{
    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $class = $scope->getClassReflection();

        if (null === $class || $class->isInterface() || $class->isEnum() || $class->isAbstract()) {
            return [];
        }

        if (!$class->implementsInterface(BuilderInterface::class)) {
            return [];
        }

        $method = $node->getOriginalNode();

        if ('seed' !== $method->name->toString()) {
            return [];
        }

        $errors = [];

        if ($method->isPublic()) {
            $errors[] = RuleErrorBuilder::message(sprintf(
                'seed() on builder %s must stay protected - a public seed() can be re-run on a live builder, and re-seeding is mutation through the back door.',
                $class->getDisplayName(),
            ))
                ->identifier('immutableTestBuilder.seedVisibility')
                ->build()
            ;
        }

        if (null === $method->stmts) {
            return $errors;
        }

        /** @var list<MethodCall> $calls */
        $calls = (new NodeFinder())->findInstanceOf($method->stmts, MethodCall::class);

        foreach ($calls as $call) {
            if (!$call->var instanceof Variable || !is_string($call->var->name) || 'this' !== $call->var->name) {
                continue;
            }

            if (!$call->name instanceof Identifier) {
                continue;
            }

            $name = $call->name->toString();

            if ('build' === $name) {
                $errors[] = RuleErrorBuilder::message(sprintf(
                    'seed() on builder %s calls build() - the builder is not complete while it is being seeded.',
                    $class->getDisplayName(),
                ))
                    ->identifier('immutableTestBuilder.seedCallsBuild')
                    ->line($call->getStartLine())
                    ->build()
                ;

                continue;
            }

            if (null !== Prefix::ofMethod($name)) {
                $errors[] = RuleErrorBuilder::message(sprintf(
                    'seed() on builder %s calls %s() - a modifier returns a new clone that seed() throws away. Assign the property directly instead.',
                    $class->getDisplayName(),
                    $name,
                ))
                    ->identifier('immutableTestBuilder.seedCallsModifier')
                    ->line($call->getStartLine())
                    ->build()
                ;
            }
        }

        return $errors;
    }
}
