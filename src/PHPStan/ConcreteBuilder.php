<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\PHPStan;

use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use Pizgariu\ImmutableTestBuilder\Contract\BuilderInterface;
use Pizgariu\ImmutableTestBuilder\Contract\Enum\Prefix;
use Pizgariu\ImmutableTestBuilder\Implementation\AbstractBuilder;

/**
 * The one place the rules decide what a concrete builder is and which declared method counts as a modifier.
 */
final class ConcreteBuilder
{
    private function __construct() {}

    public static function fromScope(Scope $scope): ?ClassReflection
    {
        $class = $scope->getClassReflection();

        if (null === $class || $class->isInterface() || $class->isTrait() || $class->isEnum() || $class->isAbstract() || $class->isAnonymous()) {
            return null;
        }

        return $class->implementsInterface(BuilderInterface::class) ? $class : null;
    }

    /**
     * The kernel-shape gate. Rules that demand mutate() or seed() only apply
     * to builders inheriting the kernel - a hand-rolled BuilderInterface
     * implementor keeps the contract rules and skips the kernel ones.
     */
    public static function kernelFromScope(Scope $scope): ?ClassReflection
    {
        $class = self::fromScope($scope);

        if (null === $class || !$class->isSubclassOf(AbstractBuilder::class)) {
            return null;
        }

        return $class;
    }

    public static function declaredModifierName(ClassMethod $method): ?string
    {
        if (!$method->isPublic() || $method->isStatic()) {
            return null;
        }

        $name = $method->name->toString();

        return null === Prefix::ofMethod($name) ? null : $name;
    }
}
