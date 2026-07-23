<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\PHPStan\Analyser;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use Pizgariu\ImmutableTestBuilder\Contract\BuilderInterface;
use Pizgariu\ImmutableTestBuilder\Implementation\AbstractBuilder;

/**
 * The one place the rules read the analysed scope as a builder: concrete()
 * answers whether the scope is a concrete builder at all, kernel() whether it
 * also inherits the kernel.
 */
final class BuilderScope
{
    private function __construct() {}

    public static function concrete(Scope $scope): ?ClassReflection
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
    public static function kernel(Scope $scope): ?ClassReflection
    {
        $class = self::concrete($scope);

        if (null === $class || !$class->isSubclassOf(AbstractBuilder::class)) {
            return null;
        }

        return $class;
    }
}
