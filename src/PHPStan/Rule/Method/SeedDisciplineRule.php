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
use PHPStan\ShouldNotHappenException;
use Pizgariu\ImmutableTestBuilder\PHPStan\KernelMethod;
use Pizgariu\ImmutableTestBuilder\Contract\Enum\Prefix;
use Pizgariu\ImmutableTestBuilder\PHPStan\Analyser\BuilderScope;

/**
 * seed() fills the perfect default and nothing else - it stays protected
 * (a public seed() could re-run on a live builder, which is mutation through
 * the back door), it never calls a modifier (the returned clone would be
 * thrown away) and it never calls build() (the builder is not complete yet).
 *
 * @implements Rule<InClassMethodNode>
 *
 * @internal
 */
final class SeedDisciplineRule implements Rule
{
    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }

    /**
     * @param InClassMethodNode $node
     * @throws ShouldNotHappenException
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $class = BuilderScope::kernel($scope);

        if (null === $class) {
            return [];
        }

        $method = $node->getOriginalNode();

        if (!KernelMethod::Seed->matches($method->name)) {
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

            if (KernelMethod::Build->matches($name)) {
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
