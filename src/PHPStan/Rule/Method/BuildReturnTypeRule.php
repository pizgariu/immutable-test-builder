<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\PHPStan\Rule\Method;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use Pizgariu\ImmutableTestBuilder\Contract\Enum\KernelMethod;
use Pizgariu\ImmutableTestBuilder\PHPStan\ConcreteBuilder;

/**
 * build() never hands back a half-thing: a concrete builder declares a
 * concrete, non-nullable return type. The impossible state throws
 * UnbuildableState - it does not leak out as null or mixed.
 *
 * @implements Rule<InClassMethodNode>
 */
final class BuildReturnTypeRule implements Rule
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
        $class = ConcreteBuilder::fromScope($scope);

        if (null === $class) {
            return [];
        }

        $method = $node->getOriginalNode();

        if (KernelMethod::Build->value !== $method->name->toString() || $method->isStatic()) {
            return [];
        }

        if ($this->isConcreteNonNullable($method->returnType)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'build() on builder %s must declare a concrete non-nullable return type - an impossible state throws UnbuildableState, it never returns null or a half-thing.',
                $class->getDisplayName(),
            ))
                ->identifier('immutableTestBuilder.buildReturnType')
                ->build(),
        ];
    }

    private function isConcreteNonNullable(?Node $returnType): bool
    {
        if (null === $returnType || $returnType instanceof NullableType) {
            return false;
        }

        if ($returnType instanceof Identifier && in_array($returnType->toLowerString(), ['mixed', 'null', 'void'], true)) {
            return false;
        }

        if ($returnType instanceof UnionType) {
            foreach ($returnType->types as $type) {
                if ($type instanceof Identifier && 'null' === $type->toLowerString()) {
                    return false;
                }
            }
        }

        return true;
    }
}
