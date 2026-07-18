<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\PHPStan\Rule\Method;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Pizgariu\ImmutableTestBuilder\Contract\BuilderInterface;
use Pizgariu\ImmutableTestBuilder\PHPStan\ModifierDsl;

/**
 * The public surface of a concrete builder is the DSL and nothing else:
 * build(), the create() factory and the modifier prefixes.
 *
 * @implements Rule<InClassMethodNode>
 */
final class ModifierNameRule implements Rule
{
    private const array ALLOWED_INSTANCE_METHODS = ['build'];

    private const array ALLOWED_STATIC_METHODS = ['create'];

    private const array FORBIDDEN_PREFIXES = ['set', 'make', 'add'];

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

        if (!$method->isPublic()) {
            return [];
        }

        $name = $method->name->toString();

        if (str_starts_with($name, '__')) {
            return [];
        }

        if ($method->isStatic()) {
            if (in_array($name, self::ALLOWED_STATIC_METHODS, true)) {
                return [];
            }

            return [
                RuleErrorBuilder::message(sprintf(
                    'Public static method %s() on builder %s is outside the DSL - the only static surface is create().',
                    $name,
                    $class->getDisplayName(),
                ))->identifier('immutableTestBuilder.staticSurface')->build(),
            ];
        }

        if (in_array($name, self::ALLOWED_INSTANCE_METHODS, true) || ModifierDsl::matches($name)) {
            return [];
        }

        foreach (self::FORBIDDEN_PREFIXES as $prefix) {
            if (1 === preg_match(sprintf('/^%s[A-Z0-9]/', $prefix), $name)) {
                return [
                    RuleErrorBuilder::message(sprintf(
                        '%s() on builder %s starts with %s* - set* promises an in-place write, add* hides whether the collection is replaced, make* says nothing. Modifiers are with*, without*, as*, from* or append* and always return a new instance.',
                        $name,
                        $class->getDisplayName(),
                        $prefix,
                    ))->identifier('immutableTestBuilder.forbiddenPrefix')->build(),
                ];
            }
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'Public method %s() on builder %s is outside the DSL - the public surface is build() and modifiers prefixed with*, without*, as*, from* or append*.',
                $name,
                $class->getDisplayName(),
            ))->identifier('immutableTestBuilder.publicSurface')->build(),
        ];
    }
}
