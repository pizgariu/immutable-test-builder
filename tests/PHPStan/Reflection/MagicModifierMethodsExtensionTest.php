<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Reflection;

use LogicException;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\StaticType;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use Pizgariu\ImmutableTestBuilder\PHPStan\Reflection\MagicModifierMethodsExtension;
use Pizgariu\ImmutableTestBuilder\Tests\Example\User;
use Pizgariu\ImmutableTestBuilder\Tests\Fixture\FreighterBuilder;
use Pizgariu\ImmutableTestBuilder\Tests\Fixture\GadgetBuilder;
use Pizgariu\ImmutableTestBuilder\Tests\Fixture\RosterBuilder;

/**
 * The extension IS the "typed with zero annotations" promise, so its answers
 * are pinned directly - which modifiers exist and with what signature. The
 * untagged ShuttleBuilder guards the same promise end to end through the
 * dogfood analysis.
 */
final class MagicModifierMethodsExtensionTest extends PHPStanTestCase
{
    /**
     * @param class-string $class
     */
    #[DataProvider('provideMethods')]
    public function testHasMethodAdvertisesExactlyWhatTheWritersHonour(string $class, string $method, bool $exists): void
    {
        $extension = new MagicModifierMethodsExtension();

        self::assertSame($exists, $extension->hasMethod($this->reflect($class), $method));
    }

    /**
     * @return iterable<string, array{class: class-string, method: string, exists: bool}>
     */
    public static function provideMethods(): iterable
    {
        yield 'with on a declared property' => ['class' => FreighterBuilder::class, 'method' => 'withCallsign', 'exists' => true];
        yield 'without on a bool' => ['class' => FreighterBuilder::class, 'method' => 'withoutArmed', 'exists' => true];
        yield 'as on a bool' => ['class' => FreighterBuilder::class, 'method' => 'asArmed', 'exists' => true];
        yield 'as on a nullable bool' => ['class' => FreighterBuilder::class, 'method' => 'asMothballed', 'exists' => true];
        yield 'including on a collection' => ['class' => FreighterBuilder::class, 'method' => 'includingDecal', 'exists' => true];
        yield 'excluding on a collection' => ['class' => FreighterBuilder::class, 'method' => 'excludingDecal', 'exists' => true];
        yield 'including through the Plural attribute' => ['class' => RosterBuilder::class, 'method' => 'includingPerson', 'exists' => true];
        yield 'sealed by NotMagic' => ['class' => RosterBuilder::class, 'method' => 'withChecksum', 'exists' => false];
        yield 'as on a non-bool' => ['class' => FreighterBuilder::class, 'method' => 'asCallsign', 'exists' => false];
        yield 'including on a non-array' => ['class' => FreighterBuilder::class, 'method' => 'includingCargo', 'exists' => false];
        yield 'without with no inferrable empty' => ['class' => GadgetBuilder::class, 'method' => 'withoutInstalledAt', 'exists' => false];
        yield 'never-magic prefix' => ['class' => FreighterBuilder::class, 'method' => 'fromManifest', 'exists' => false];
        yield 'no matching property' => ['class' => FreighterBuilder::class, 'method' => 'withSerial', 'exists' => false];
        yield 'no DSL prefix' => ['class' => FreighterBuilder::class, 'method' => 'launchTowards', 'exists' => false];
        yield 'not a builder at all' => ['class' => User::class, 'method' => 'withName', 'exists' => false];
    }

    public function testWithSignatureFeedsThePropertyTypeAndReturnsTheBuilder(): void
    {
        $method = (new MagicModifierMethodsExtension())->getMethod($this->reflect(FreighterBuilder::class), 'withCallsign');
        $variant = ParametersAcceptorSelector::selectSingle($method->getVariants());
        $parameters = $variant->getParameters();

        self::assertSame('withCallsign', $method->getName());
        self::assertFalse($method->isStatic());
        self::assertCount(1, $parameters);
        self::assertSame('callsign', $parameters[0]->getName());
        self::assertSame('string', $parameters[0]->getType()->describe(VerbosityLevel::precise()));
        self::assertFalse($parameters[0]->isOptional());
        self::assertInstanceOf(StaticType::class, $variant->getReturnType());
    }

    public function testAsSignatureTakesOneOptionalBoolDefaultingToTrue(): void
    {
        $method = (new MagicModifierMethodsExtension())->getMethod($this->reflect(FreighterBuilder::class), 'asArmed');
        $parameters = ParametersAcceptorSelector::selectSingle($method->getVariants())->getParameters();

        self::assertCount(1, $parameters);
        self::assertSame('armed', $parameters[0]->getName());
        self::assertTrue($parameters[0]->isOptional());
        self::assertSame('bool', $parameters[0]->getType()->describe(VerbosityLevel::precise()));
        self::assertSame('true', $parameters[0]->getDefaultValue()?->describe(VerbosityLevel::precise()));
    }

    public function testAsSignatureKeepsTheNullOfANullableFlag(): void
    {
        $method = (new MagicModifierMethodsExtension())->getMethod($this->reflect(FreighterBuilder::class), 'asMothballed');
        $parameters = ParametersAcceptorSelector::selectSingle($method->getVariants())->getParameters();

        self::assertSame('bool|null', $parameters[0]->getType()->describe(VerbosityLevel::precise()));
    }

    public function testWithoutSignatureTakesNothing(): void
    {
        $method = (new MagicModifierMethodsExtension())->getMethod($this->reflect(FreighterBuilder::class), 'withoutArmed');

        self::assertCount(0, ParametersAcceptorSelector::selectSingle($method->getVariants())->getParameters());
    }

    public function testIncludingSignatureSpeaksTheSingular(): void
    {
        $method = (new MagicModifierMethodsExtension())->getMethod($this->reflect(FreighterBuilder::class), 'includingDecal');
        $parameters = ParametersAcceptorSelector::selectSingle($method->getVariants())->getParameters();

        self::assertCount(1, $parameters);
        self::assertSame('decal', $parameters[0]->getName());
        self::assertFalse($parameters[0]->isOptional());
    }

    public function testGetMethodRefusesWhatHasMethodRefused(): void
    {
        $extension = new MagicModifierMethodsExtension();

        $this->expectException(LogicException::class);

        $extension->getMethod($this->reflect(RosterBuilder::class), 'withChecksum');
    }

    /**
     * @param class-string $class
     */
    private function reflect(string $class): ClassReflection
    {
        return self::createReflectionProvider()->getClass($class);
    }
}
