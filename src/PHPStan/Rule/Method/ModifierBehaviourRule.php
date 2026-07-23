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
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use Pizgariu\ImmutableTestBuilder\PHPStan\KernelMethod;
use Pizgariu\ImmutableTestBuilder\Contract\Enum\Prefix;
use Pizgariu\ImmutableTestBuilder\PHPStan\Analyser\BuilderScope;
use Pizgariu\ImmutableTestBuilder\PHPStan\Node\DeclaredModifier;

/**
 * A declared modifier's body must keep the promise its prefix makes. The
 * kernel funnels every mutation through the closure or property map handed
 * to mutate(), so the semantics are statically checkable right there:
 * without* takes no parameters, as* at most one optional bool, the feeding
 * prefixes take at least one, without* only writes empty values, including*
 * appends, excluding* never appends, and having* writes more than one
 * property or it is a with* in a having* costume.
 *
 * @implements Rule<InClassMethodNode>
 */
final class ModifierBehaviourRule implements Rule
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
        $class = BuilderScope::kernel($scope);

        if (null === $class) {
            return [];
        }

        $method = $node->getOriginalNode();
        $name = DeclaredModifier::name($method);

        if (null === $name) {
            return [];
        }

        $prefix = Prefix::ofMethod($name);

        if (null === $prefix) {
            return [];
        }

        $errors = [];
        $parameterCount = count($method->getParams());
        $display = $class->getDisplayName();

        if ($prefix->acceptsOptionalParameter()) {
            if (!$this->declaresAtMostOneOptionalBoolFlag($method->getParams())) {
                $errors[] = RuleErrorBuilder::message(sprintf(
                    '%s() on builder %s may declare at most one optional bool parameter - as* raises the flag by default and an explicit bool or null only overrides it.',
                    $name,
                    $display,
                ))
                    ->identifier('immutableTestBuilder.modifierArity')
                    ->build()
                ;
            }
        } elseif (!$prefix->feeds()) {
            if ($parameterCount > 0) {
                $errors[] = RuleErrorBuilder::message(sprintf(
                    '%s() on builder %s must not take parameters - %s* modifiers name the entire change in their method name.',
                    $name,
                    $display,
                    $prefix->value,
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

        $mutation = $this->mutationExpression($method->stmts);

        if (null === $mutation) {
            return $errors;
        }

        $appends = 0;
        $propertyWrites = [];
        $nonEmptyWrites = 0;

        if ($mutation instanceof Array_) {
            foreach ($mutation->items as $item) {
                if (!$item->key instanceof String_) {
                    continue;
                }

                $propertyWrites[$item->key->value] = true;

                if ($item->value instanceof Array_ && $this->containsSpread($item->value)) {
                    ++$appends;
                    ++$nonEmptyWrites;

                    continue;
                }

                if (!$this->isEmptyingValue($item->value)) {
                    ++$nonEmptyWrites;
                }
            }
        } else {
            $finder = new NodeFinder();
            /** @var list<Assign> $assignments */
            $assignments = $finder->findInstanceOf([$mutation], Assign::class);

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
     * @param array<Param> $params
     */
    private function declaresAtMostOneOptionalBoolFlag(array $params): bool
    {
        if ([] === $params) {
            return true;
        }

        if (1 !== count($params)) {
            return false;
        }

        $param = $params[0];

        if (null === $param->default) {
            return false;
        }

        $type = $param->type instanceof NullableType ? $param->type->type : $param->type;

        return $type instanceof Identifier && 'bool' === $type->toLowerString();
    }

    /**
     * @param array<Node\Stmt>|null $statements
     */
    private function mutationExpression(?array $statements): Closure|ArrowFunction|Array_|null
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

        if (!$call->name instanceof Identifier || KernelMethod::Mutate->value !== $call->name->toString() || $call->isFirstClassCallable()) {
            return null;
        }

        $arguments = $call->getArgs();

        if (!isset($arguments[0])) {
            return null;
        }

        $mutation = $arguments[0]->value;

        return $mutation instanceof Closure || $mutation instanceof ArrowFunction || $mutation instanceof Array_ ? $mutation : null;
    }

    private function containsSpread(Array_ $array): bool
    {
        foreach ($array->items as $item) {
            if ($item->unpack) {
                return true;
            }
        }

        return false;
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
