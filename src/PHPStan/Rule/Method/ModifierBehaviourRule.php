<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\PHPStan\Rule\Method;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Pizgariu\ImmutableTestBuilder\Contract\BuilderInterface;
use Pizgariu\ImmutableTestBuilder\Prefix;

/**
 * A declared modifier's body must keep the promise its prefix makes. The
 * kernel funnels every mutation through the closure handed to mutate(), so
 * the semantics are statically checkable right there: without* and as* take
 * no parameters, the feeding prefixes take at least one, without* only
 * writes empty values, including* appends, excluding* never appends, and
 * having* writes more than one property or it is a with* in a having*
 * costume.
 *
 * @implements Rule<InClassMethodNode>
 */
final class ModifierBehaviourRule implements Rule
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

        if (!$method->isPublic() || $method->isStatic()) {
            return [];
        }

        $name = $method->name->toString();
        $prefix = Prefix::ofMethod($name);

        if (null === $prefix) {
            return [];
        }

        $errors = [];
        $parameterCount = count($method->getParams());
        $display = $class->getDisplayName();

        if (!$prefix->takesParameters()) {
            if ($parameterCount > 0) {
                $errors[] = RuleErrorBuilder::message(sprintf(
                    '%s() on builder %s must not take parameters - a %s modifier names the entire change in its method name.',
                    $name,
                    $display,
                    implode(' or ', array_map(static fn (Prefix $bare): string => $bare->value . '*', Prefix::parameterless())),
                ))
                    ->identifier('immutableTestBuilder.modifierArity')
                    ->build()
                ;
            }
        } elseif (0 === $parameterCount) {
            $errors[] = RuleErrorBuilder::message(sprintf(
                '%s() on builder %s must take a parameter - %s* feeds the builder outside data. A parameterless modifier is an as*.',
                $name,
                $display,
                $prefix->value,
            ))
                ->identifier('immutableTestBuilder.modifierArity')
                ->build()
            ;
        }

        $mutation = $this->mutationClosure($method->stmts);

        if (null === $mutation) {
            return $errors;
        }

        $finder = new NodeFinder();
        /** @var list<Assign> $assignments */
        $assignments = $finder->findInstanceOf([$mutation], Assign::class);

        $appends = 0;
        $propertyWrites = [];
        $nonEmptyWrites = 0;

        foreach ($assignments as $assignment) {
            if ($assignment->var instanceof ArrayDimFetch && null === $assignment->var->dim) {
                ++$appends;

                continue;
            }

            if ($assignment->var instanceof PropertyFetch && $assignment->var->name instanceof Identifier) {
                $propertyWrites[$assignment->var->name->toString()] = true;

                if (!$this->isEmptyingValue($assignment->expr)) {
                    ++$nonEmptyWrites;
                }
            }
        }

        if (Prefix::Without === $prefix && $nonEmptyWrites > 0) {
            $errors[] = RuleErrorBuilder::message(sprintf(
                '%s() on builder %s assigns a non-empty value - without* promises emptying or nullifying. A real value makes it a with* wearing a mask.',
                $name,
                $display,
            ))
                ->identifier('immutableTestBuilder.withoutSemantics')
                ->build()
            ;
        }

        if (Prefix::Including === $prefix && 0 === $appends) {
            $errors[] = RuleErrorBuilder::message(sprintf(
                '%s() on builder %s never appends - including* promises extending a collection with []=. Replacing the whole collection is a with*.',
                $name,
                $display,
            ))
                ->identifier('immutableTestBuilder.includingSemantics')
                ->build()
            ;
        }

        if (Prefix::Excluding === $prefix && $appends > 0) {
            $errors[] = RuleErrorBuilder::message(sprintf(
                '%s() on builder %s appends with []= - excluding* promises shrinking a collection, not growing it.',
                $name,
                $display,
            ))
                ->identifier('immutableTestBuilder.excludingSemantics')
                ->build()
            ;
        }

        if (Prefix::Having === $prefix && count($propertyWrites) < 2) {
            $errors[] = RuleErrorBuilder::message(sprintf(
                '%s() on builder %s mutates a single property - having* is for one inseparable multi-property concept. A single write is a with*.',
                $name,
                $display,
            ))
                ->identifier('immutableTestBuilder.havingSemantics')
                ->build()
            ;
        }

        return $errors;
    }

    /**
     * @param array<Node\Stmt>|null $statements
     */
    private function mutationClosure(?array $statements): Closure|ArrowFunction|null
    {
        if (null === $statements || 1 !== count($statements)) {
            return null;
        }

        $statement = $statements[0];

        if (!$statement instanceof Return_ || !$statement->expr instanceof MethodCall) {
            return null;
        }

        $call = $statement->expr;

        if (!$call->var instanceof Variable || !is_string($call->var->name) || 'this' !== $call->var->name) {
            return null;
        }

        if (!$call->name instanceof Identifier || 'mutate' !== $call->name->toString() || $call->isFirstClassCallable()) {
            return null;
        }

        $arguments = $call->getArgs();

        if (!isset($arguments[0])) {
            return null;
        }

        $mutation = $arguments[0]->value;

        return $mutation instanceof Closure || $mutation instanceof ArrowFunction ? $mutation : null;
    }

    private function isEmptyingValue(Node $value): bool
    {
        if ($value instanceof ConstFetch) {
            $constant = strtolower($value->name->toString());

            return 'null' === $constant || 'false' === $constant;
        }

        if ($value instanceof Array_) {
            return [] === $value->items;
        }

        if ($value instanceof String_) {
            return '' === $value->value;
        }

        if ($value instanceof LNumber) {
            return 0 === $value->value;
        }

        if ($value instanceof DNumber) {
            return 0.0 === $value->value;
        }

        return false;
    }
}
