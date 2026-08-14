<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Rector\Class_;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\UnionType;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDocParser\Ast\PhpDoc\MethodTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\MethodTagValueParameterNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\Reflection\ClassReflection;
use Pizgariu\ImmutableTestBuilder\Contract\Enum\Prefix;
use Pizgariu\ImmutableTestBuilder\Implementation\Resolver\PropertyResolver;
use Pizgariu\ImmutableTestBuilder\PHPStan\Analyser\BuilderScope;
use Pizgariu\ImmutableTestBuilder\PHPStan\KernelMethod;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Rector\Comments\NodeDocBlock\DocBlockUpdater;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\Rector\AbstractRector;
use Rector\StaticTypeMapper\StaticTypeMapper;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Replaces a modifier whose body only does what the kernel already derives from
 * the property declaration with a @method tag on the class. The magic __call
 * takes over with identical behaviour and IDEs keep the autocomplete through the
 * annotation.
 *
 * Handled forms, all in the exact property-map mutate() - with*(x) assigning the
 * bare parameter, as*() writing true into a bool, and without*() writing the
 * empty value the kernel infers from the property type (null for nullable, then
 * [], '', 0, 0.0, false). Closure bodies, transforming values, parameters with
 * defaults and the collection prefixes are left alone.
 *
 * A matching shape is not enough on its own - the method goes only when the
 * kernel would answer the same call, which is also why the gate demands the
 * kernel itself and why the property question goes to PropertyResolver rather
 * than being asked again here. A hand-rolled builder with its own mutate()
 * writes the same one-liner and has no __call to catch the name once the method
 * is gone, a property sealed with #[NotMagic] makes the derivation refuse, and
 * so does a flag the writers will not treat as one. Each of those keeps its
 * body.
 *
 * The tag's return type is self because concrete builders are final, so self is
 * exact and unambiguous where a leading static would re-parse as a static method.
 */
final class RemoveRedundantModifierRector extends AbstractRector
{
    public function __construct(
        private readonly PhpDocInfoFactory $phpDocInfoFactory,
        private readonly DocBlockUpdater $docBlockUpdater,
        private readonly StaticTypeMapper $staticTypeMapper,
    ) {}

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Replace a modifier the kernel already derives with a @method tag, leaving the magic __call to handle it while IDEs keep the hint.',
            [
                new CodeSample(
                    <<<'BEFORE'
final class UserBuilder extends AbstractBuilder
{
    private string $name;

    private ?string $email;

    public function withName(string $name): static
    {
        return $this->mutate(['name' => $name]);
    }

    public function withoutEmail(): static
    {
        return $this->mutate(['email' => null]);
    }
}
BEFORE,
                    <<<'AFTER'
/**
 * @method self withName(string $name)
 * @method self withoutEmail()
 */
final class UserBuilder extends AbstractBuilder
{
    private string $name;

    private ?string $email;
}
AFTER,
                ),
            ],
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (!$node instanceof Class_) {
            return null;
        }

        $scope = $node->getAttribute(AttributeKey::SCOPE);

        if (!$scope instanceof Scope) {
            return null;
        }

        $class = BuilderScope::kernel($scope);

        if (null === $class) {
            return null;
        }

        $propertyTypes = $this->propertyTypes($node);
        $methodTags = [];

        foreach ($node->stmts as $key => $stmt) {
            if (!$stmt instanceof ClassMethod) {
                continue;
            }

            $tag = $this->redundantModifierTag($stmt, $class, $propertyTypes);

            if (null === $tag) {
                continue;
            }

            $methodTags[] = $tag;
            unset($node->stmts[$key]);
        }

        if ([] === $methodTags) {
            return null;
        }

        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($node);

        foreach ($methodTags as $methodTag) {
            $phpDocInfo->addTagValueNode($methodTag);
        }

        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);

        return $node;
    }

    /**
     * @return array<string, Node|null>
     */
    private function propertyTypes(Class_ $class): array
    {
        $types = [];

        foreach ($class->stmts as $stmt) {
            if (!$stmt instanceof Property) {
                continue;
            }

            foreach ($stmt->props as $property) {
                $types[$property->name->toString()] = $stmt->type;
            }
        }

        return $types;
    }

    /**
     * @param array<string, Node|null> $propertyTypes
     */
    private function redundantModifierTag(ClassMethod $method, ClassReflection $class, array $propertyTypes): ?MethodTagValueNode
    {
        if (!$method->isPublic() || $method->isStatic() || !$this->declaresStaticReturn($method->returnType)) {
            return null;
        }

        $name = $this->getName($method);
        $prefix = Prefix::ofMethod($name);

        if (Prefix::With !== $prefix && Prefix::As !== $prefix && Prefix::Without !== $prefix) {
            return null;
        }

        $item = $this->singleMutateMapItem($method);

        if (null === $item) {
            return null;
        }

        [$key, $value] = $item;

        $native = $class->getNativeReflection();

        if ($prefix->propertyCandidates($name)[0] !== $key || !$native->hasProperty($key)) {
            return null;
        }

        // Asked of the kernel rather than restated here. This deletes a method only where the
        // derivation would answer the same call, so the two cannot be allowed to drift apart.
        if (!PropertyResolver::derivable($native->getProperty($key))) {
            return null;
        }

        if (!$this->valueMatchesMagic($prefix, $method, $value, $propertyTypes[$key] ?? null)) {
            return null;
        }

        return new MethodTagValueNode(false, new IdentifierTypeNode('self'), $name, $this->methodTagParameters($prefix, $method), '');
    }

    private function declaresStaticReturn(?Node $returnType): bool
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
     * @return array{string, Expr}|null
     */
    private function singleMutateMapItem(ClassMethod $node): ?array
    {
        if (null === $node->stmts || 1 !== count($node->stmts)) {
            return null;
        }

        $return = $node->stmts[0];

        if (!$return instanceof Return_ || !$return->expr instanceof MethodCall) {
            return null;
        }

        $call = $return->expr;

        if (!$call->var instanceof Variable || 'this' !== $call->var->name) {
            return null;
        }

        if (!KernelMethod::Mutate->matches($call->name)) {
            return null;
        }

        $arguments = $call->getArgs();

        if (1 !== count($arguments) || !$arguments[0]->value instanceof Array_) {
            return null;
        }

        $array = $arguments[0]->value;

        if (1 !== count($array->items)) {
            return null;
        }

        $arrayItem = $array->items[0];

        if (!$arrayItem->key instanceof String_ || $arrayItem->unpack) {
            return null;
        }

        return [$arrayItem->key->value, $arrayItem->value];
    }

    private function valueMatchesMagic(Prefix $prefix, ClassMethod $node, Expr $value, ?Node $propertyType): bool
    {
        if (Prefix::As === $prefix) {
            return [] === $node->getParams() && $this->isBoolFlag($propertyType) && $this->isConst($value, 'true');
        }

        if (Prefix::Without === $prefix) {
            return [] === $node->getParams() && $this->matchesEmptyValue($value, $propertyType);
        }

        $parameters = $node->getParams();

        if (1 !== count($parameters) || null !== $parameters[0]->default) {
            return false;
        }

        $parameter = $parameters[0];

        return $parameter->var instanceof Variable
            && is_string($parameter->var->name)
            && $value instanceof Variable
            && $value->name === $parameter->var->name;
    }

    private function matchesEmptyValue(Expr $value, ?Node $propertyType): bool
    {
        if ($propertyType instanceof NullableType) {
            return $this->isConst($value, 'null');
        }

        if ($propertyType instanceof UnionType) {
            foreach ($propertyType->types as $type) {
                if ($type instanceof Identifier && 'null' === $type->toLowerString()) {
                    return $this->isConst($value, 'null');
                }
            }

            return false;
        }

        if (!$propertyType instanceof Identifier) {
            return false;
        }

        return match ($propertyType->toLowerString()) {
            'array' => $value instanceof Array_ && [] === $value->items,
            'string' => $value instanceof String_ && '' === $value->value,
            'int' => $value instanceof LNumber && 0 === $value->value,
            'float' => $value instanceof DNumber && 0.0 === $value->value,
            'bool' => $this->isConst($value, 'false'),
            default => false,
        };
    }

    private function isConst(Expr $value, string $name): bool
    {
        return $value instanceof ConstFetch && $name === strtolower($value->name->toString());
    }

    /**
     * The flag writer answers bool and nullable bool and refuses everything
     * else, so those are the only types whose as*() body may go.
     */
    private function isBoolFlag(?Node $propertyType): bool
    {
        if ($propertyType instanceof NullableType) {
            $propertyType = $propertyType->type;
        }

        return $propertyType instanceof Identifier && 'bool' === $propertyType->toLowerString();
    }

    /**
     * @return list<MethodTagValueParameterNode>
     */
    private function methodTagParameters(Prefix $prefix, ClassMethod $method): array
    {
        if (Prefix::With !== $prefix) {
            return [];
        }

        $parameter = $method->getParams()[0];
        $type = null === $parameter->type
            ? null
            : $this->staticTypeMapper->mapPHPStanTypeToPHPStanPhpDocTypeNode(
                $this->staticTypeMapper->mapPhpParserNodePHPStanType($parameter->type),
            );

        $variable = $parameter->var;
        $parameterName = $variable instanceof Variable && is_string($variable->name) ? $variable->name : 'value';

        return [new MethodTagValueParameterNode($type, false, false, '$' . $parameterName, null)];
    }
}
