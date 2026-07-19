<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\PHPStan\Reflection;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Type\ArrayType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use Pizgariu\ImmutableTestBuilder\Enum\Prefix;
use Pizgariu\ImmutableTestBuilder\Implementation\AbstractBuilder;
use LogicException;

/**
 * Teaches PHPStan every magic modifier the kernel implements at runtime.
 * withName() exists because $name exists - the signature is derived from the
 * property declaration and the prefix semantics, so magic calls are fully
 * typed with zero annotations and no mapper.
 */
final class MagicModifierMethodsExtension implements MethodsClassReflectionExtension
{
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        return null !== $this->magicPropertyName($classReflection, $methodName);
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        $prefix = Prefix::ofMethod($methodName);
        $property = $this->magicPropertyName($classReflection, $methodName);

        if (null === $prefix || null === $property) {
            throw new LogicException(sprintf('getMethod(%s) called without a positive hasMethod().', $methodName));
        }

        return new MagicModifierMethod($classReflection, $methodName, $this->parametersFor($classReflection, $prefix, $property, $methodName));
    }

    private function magicPropertyName(ClassReflection $classReflection, string $methodName): ?string
    {
        if (!$classReflection->isSubclassOf(AbstractBuilder::class)) {
            return null;
        }

        $prefix = Prefix::ofMethod($methodName);

        if (null === $prefix || !$prefix->autoImplementable()) {
            return null;
        }

        foreach ($prefix->propertyCandidates($methodName) as $candidate) {
            if ($classReflection->hasNativeProperty($candidate) && !$classReflection->getNativeProperty($candidate)->isStatic()) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return list<MagicModifierParameter>
     */
    private function parametersFor(ClassReflection $classReflection, Prefix $prefix, string $property, string $methodName): array
    {
        if (!$prefix->takesParameters()) {
            return [];
        }

        return [
            new MagicModifierParameter(
                Prefix::With === $prefix ? $property : $prefix->propertyCandidates($methodName)[0],
                $this->parameterType($classReflection, $prefix, $property),
            ),
        ];
    }

    private function parameterType(ClassReflection $classReflection, Prefix $prefix, string $property): Type
    {
        $propertyType = $classReflection->getNativeProperty($property)->getNativeType();

        if (Prefix::With === $prefix) {
            return $propertyType;
        }

        if ($propertyType instanceof ArrayType) {
            return $propertyType->getItemType();
        }

        return new MixedType();
    }
}
