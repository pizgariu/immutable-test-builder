<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\PHPStan\Reflection;

use LogicException;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Pizgariu\ImmutableTestBuilder\Contract\Enum\Prefix;
use Pizgariu\ImmutableTestBuilder\Implementation\AbstractBuilder;
use Pizgariu\ImmutableTestBuilder\Implementation\Resolver\PropertyResolver;

/**
 * Teaches PHPStan every magic modifier the kernel implements at runtime.
 * withName() exists because $name exists - the signature is derived from the
 * property declaration and the prefix semantics, so magic calls are fully
 * typed with zero annotations and no mapper. A method the runtime would
 * refuse on the property type (as* on a non-bool, including* on a non-array,
 * without* with no inferrable empty value) is not advertised at all, so the
 * mismatch surfaces as an undefined-method error at the call site.
 *
 * @internal
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

        $property = PropertyResolver::resolve($classReflection->getNativeReflection(), $prefix, $methodName);

        if (null === $property) {
            return null;
        }

        $name = $property->getName();

        if (!$this->propertyTypeSupports($prefix, $classReflection->getNativeProperty($name)->getNativeType())) {
            return null;
        }

        return $name;
    }

    /**
     * Mirrors the writers' own refusals - a prefix is only derived for a
     * property whose type the runtime write can honour.
     */
    private function propertyTypeSupports(Prefix $prefix, Type $type): bool
    {
        if ($type instanceof MixedType) {
            return true;
        }

        $bare = TypeCombinator::removeNull($type);

        return match ($prefix) {
            Prefix::As => $bare->isBoolean()->yes(),
            Prefix::Including, Prefix::Excluding => $bare->isArray()->yes(),
            Prefix::Without => TypeCombinator::containsNull($type)
                || $bare->isArray()->yes()
                || $bare->isString()->yes()
                || $bare->isInteger()->yes()
                || $bare->isFloat()->yes()
                || $bare->isBoolean()->yes(),
            Prefix::With => true,
            Prefix::From, Prefix::For, Prefix::Having => false,
        };
    }

    /**
     * @return list<MagicModifierParameter>
     */
    private function parametersFor(ClassReflection $classReflection, Prefix $prefix, string $property, string $methodName): array
    {
        if ($prefix->acceptsOptionalParameter()) {
            return [
                new MagicModifierParameter(
                    $property,
                    $classReflection->getNativeProperty($property)->getNativeType(),
                    new ConstantBooleanType(true),
                ),
            ];
        }

        if (!$prefix->feeds()) {
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
