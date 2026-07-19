<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\PHPStan\Rule\Method;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use Pizgariu\ImmutableTestBuilder\Contract\Enum\KernelMethod;
use Pizgariu\ImmutableTestBuilder\Contract\Enum\Prefix;
use Pizgariu\ImmutableTestBuilder\PHPStan\ConcreteBuilder;

/**
 * The public surface of a concrete builder is the DSL and nothing else: build(), the create() factory and the modifier prefixes.
 *
 * @implements Rule<InClassMethodNode>
 */
final class ModifierNameRule implements Rule
{
    private const array FORBIDDEN_PREFIXES = ['set', 'make', 'add'];

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

        if (!$method->isPublic()) {
            return [];
        }

        $name = $method->name->toString();

        if (str_starts_with($name, '__')) {
            return [];
        }

        if ($method->isStatic()) {
            if (KernelMethod::Create->value === $name) {
                return [];
            }

            return [
                RuleErrorBuilder::message(sprintf(
                    'Public static method %s() on builder %s is outside the DSL - the only static surface is create().',
                    $name,
                    $class->getDisplayName(),
                ))
                    ->identifier('immutableTestBuilder.staticSurface')
                    ->build(),
            ];
        }

        if (KernelMethod::Build->value === $name || null !== Prefix::ofMethod($name)) {
            return [];
        }

        foreach (self::FORBIDDEN_PREFIXES as $prefix) {
            if (1 === preg_match(sprintf('/^%s[A-Z0-9]/', $prefix), $name)) {
                return [
                    RuleErrorBuilder::message(sprintf(
                        '%s() on builder %s starts with %s* - set* promises an in-place write, add* hides whether the collection is replaced, make* says nothing. Modifiers are with*, without*, as*, from*, for*, including*, excluding* or having* and always return a new instance.',
                        $name,
                        $class->getDisplayName(),
                        $prefix,
                    ))
                        ->identifier('immutableTestBuilder.forbiddenPrefix')
                        ->build(),
                ];
            }
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'Public method %s() on builder %s is outside the DSL - the public surface is build() and modifiers prefixed with*, without*, as*, from*, for*, including*, excluding* or having*.',
                $name,
                $class->getDisplayName(),
            ))
                ->identifier('immutableTestBuilder.publicSurface')
                ->build(),
        ];
    }
}
