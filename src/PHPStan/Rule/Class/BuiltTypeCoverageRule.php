<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\PHPStan\Rule\Class;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\VerbosityLevel;
use Pizgariu\ImmutableTestBuilder\Contract\Attribute\CoversBuiltType;
use Pizgariu\ImmutableTestBuilder\PHPStan\Analyser\BuilderScope;
use Pizgariu\ImmutableTestBuilder\PHPStan\KernelMethod;

/**
 * Holds a builder to the promise it made. A builder carrying #[CoversBuiltType]
 * says it owns every required ingredient of the type it builds, so this reads the
 * built type off build()'s return type and reports each required constructor
 * parameter the builder has no writable property for. A field added to the built
 * type then turns into an analysis error on the builder rather than a fixture
 * nobody can vary.
 *
 * It is the only rule in extension.neon that waits to be asked, because it is the
 * only one whose subject is not the code in front of it. A builder pinning an
 * ingredient to one literal still builds a complete object, so the gap is about
 * what a test can reach and not about correctness, and only the author knows
 * which of the two a missing property is. Without the attribute that error would
 * be a preference. With it the builder stated the claim and this checks it.
 *
 * An ingredient is matched BY NAME, which is the whole shape of the promise. A
 * builder free to model $emailAddress as $email is free not to make the promise,
 * and one that makes it is saying its own vocabulary is the built type's.
 *
 * A promise that cannot be checked is reported too, since silence would read as
 * coverage. Two boundaries stay deliberately outside all of it. A property this
 * scope cannot see is not counted even where a base-declared modifier could write
 * it, so the message says what this class can reach rather than what some test
 * somewhere might. And a property here that shadows an ancestor's private one of
 * the same name counts, because a name is all this rule reads - which is why the
 * message offers widening the base property too, so shadowing is never the only
 * route it points at.
 *
 * @implements Rule<InClassNode>
 *
 * @internal
 */
final class BuiltTypeCoverageRule implements Rule
{
    public function __construct(private readonly ReflectionProvider $reflectionProvider) {}

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @param InClassNode $node
     * @throws ShouldNotHappenException
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $base = BuilderScope::base($scope);

        if (null !== $base && $this->promises($base)) {
            return [
                RuleErrorBuilder::message(sprintf(
                    'Builder %s declares #[CoversBuiltType] on an abstract base, where the promise is never checked, because it is not inherited. Move the attribute onto each builder that is finished.',
                    $base->getDisplayName(),
                ))
                    ->identifier('immutableTestBuilder.builtTypeCoverageUnusable')
                    ->build(),
            ];
        }

        $class = BuilderScope::concrete($scope);

        if (null === $class || !$this->promises($class)) {
            return [];
        }

        if (!$class->hasNativeMethod(KernelMethod::Build->value)) {
            return [$this->unusable($class, 'the builder declares no build() method')];
        }

        // Counted rather than selected, here and again on the constructor below. selectSingle()
        // throws on a multi-variant signature, and a throw here aborts the whole file's analysis.
        $variants = $class->getNativeMethod(KernelMethod::Build->value)->getVariants();

        if (1 !== count($variants)) {
            return [$this->unusable($class, 'build() declares more than one signature, so it names no single built type')];
        }

        $returned = $variants[0]->getReturnType();
        $names = $returned->getObjectClassNames();

        if (1 !== count($names)) {
            return [$this->unusable($class, sprintf('build() returns %s, which names no single constructed class', $returned->describe(VerbosityLevel::typeOnly())))];
        }

        if ($names[0] === $class->getName() || $class->isSubclassOf($names[0])) {
            return [$this->unusable($class, 'build() hands back the builder itself, so it names no built type')];
        }

        if (!$this->reflectionProvider->hasClass($names[0])) {
            return [$this->unusable($class, sprintf('the analysis cannot reflect %s', $names[0]))];
        }

        $built = $this->reflectionProvider->getClass($names[0]);

        if ($built->isInterface() || $built->isEnum() || $built->isAbstract()) {
            return [$this->unusable($class, sprintf('%s is never constructed directly', $built->getDisplayName()))];
        }

        if (!$built->hasConstructor()) {
            return [$this->unusable($class, sprintf('%s declares no constructor, so it names no required ingredients', $built->getDisplayName()))];
        }

        $constructor = $built->getConstructor();

        if (!$constructor->isPublic()) {
            return [$this->unusable($class, sprintf('the constructor of %s is not public, so its required ingredients are not the ones build() passes', $built->getDisplayName()))];
        }

        $variants = $constructor->getVariants();

        if (1 !== count($variants)) {
            return [$this->unusable($class, sprintf('the constructor of %s declares more than one signature, so it names no single set of required ingredients', $built->getDisplayName()))];
        }

        $inheritsKernel = null !== BuilderScope::kernel($scope);
        $errors = [];

        foreach ($variants[0]->getParameters() as $parameter) {
            if ($parameter->isOptional() || $this->writable($class, $parameter->getName(), $inheritsKernel)) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(sprintf(
                'Builder %s declares #[CoversBuiltType] but its own scope cannot write $%s, which %s requires, so nothing here varies that ingredient. Declare a private $%s, widen a base-declared one to protected, or drop the attribute if this builder fixes the value on purpose.',
                $class->getDisplayName(),
                $parameter->getName(),
                $built->getDisplayName(),
                $parameter->getName(),
            ))
                ->identifier('immutableTestBuilder.builtTypeCoverage')
                ->build()
            ;
        }

        return $errors;
    }

    /**
     * PHP does not inherit a class attribute and neither does the promise, so
     * each class answers for its own declaration only. The name-only lookup is
     * load-bearing - asking with ReflectionAttribute::IS_INSTANCEOF makes the
     * adapter reflect every attribute on the class, and one unresolvable name
     * there would drop the whole class from analysis.
     */
    private function promises(ClassReflection $class): bool
    {
        return [] !== $class->getNativeReflection()->getAttributes(CoversBuiltType::class);
    }

    /**
     * Writable is what mutate() means by it when the builder inherits the
     * kernel - present, not static, not readonly, because a clone carries
     * neither. A hand-rolled implementor owes the kernel nothing and may
     * replace a whole readonly instance per modifier, so it is only asked
     * for presence.
     */
    private function writable(ClassReflection $class, string $property, bool $inheritsKernel): bool
    {
        $native = $class->getNativeReflection();

        if (!$native->hasProperty($property)) {
            return false;
        }

        if (!$inheritsKernel) {
            return true;
        }

        $declared = $native->getProperty($property);

        return !$declared->isStatic() && !$declared->isReadOnly();
    }

    /**
     * The envelope every unverifiable promise is returned in. Each caller
     * supplies only the reason, so the sentence around it cannot drift apart
     * across the shapes that reach it.
     *
     * @throws ShouldNotHappenException
     */
    private function unusable(ClassReflection $class, string $reason): IdentifierRuleError
    {
        return RuleErrorBuilder::message(sprintf(
            'Builder %s declares #[CoversBuiltType] and the promise cannot be checked, because %s. Point build() at the class this builder produces, or drop the attribute.',
            $class->getDisplayName(),
            $reason,
        ))
            ->identifier('immutableTestBuilder.builtTypeCoverageUnusable')
            ->build()
        ;
    }
}
