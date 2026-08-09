<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Data\BuiltTypeCoverage;

use Pizgariu\ImmutableTestBuilder\Contract\Attribute\CoversBuiltType;
use Pizgariu\ImmutableTestBuilder\Contract\BuilderInterface;
use Pizgariu\ImmutableTestBuilder\Implementation\AbstractBuilder;
use Pizgariu\ImmutableTestBuilder\Tests\Fixture\AbstractStationBuilder;
use Pizgariu\ImmutableTestBuilder\Tests\Fixture\Beacon;
use Pizgariu\ImmutableTestBuilder\Tests\Fixture\Derelict;
use Pizgariu\ImmutableTestBuilder\Tests\Fixture\Widget;
use ReflectionMethod;

/**
 * @extends AbstractBuilder<Widget>
 */
#[CoversBuiltType]
final class CoveringWidgetBuilder extends AbstractBuilder
{
    private string $sku;

    private int $quantity;

    public function build(): Widget
    {
        return new Widget($this->sku, $this->quantity);
    }

    protected function seed(): void
    {
        $this->sku = 'W-1';
        $this->quantity = 1;
    }
}

/**
 * @extends AbstractBuilder<Widget>
 */
#[CoversBuiltType]
final class LeakyWidgetBuilder extends AbstractBuilder
{
    private string $sku;

    public function build(): Widget
    {
        return new Widget($this->sku, 0);
    }

    protected function seed(): void
    {
        $this->sku = 'W-1';
    }
}

/**
 * The negative control. Identical to LeakyWidgetBuilder without the promise, so
 * it proves the attribute is read rather than assumed. Pinning $quantity to one
 * literal is a legitimate fixture, and nothing here says otherwise.
 *
 * @extends AbstractBuilder<Widget>
 */
final class UnpromisedWidgetBuilder extends AbstractBuilder
{
    private string $sku;

    public function build(): Widget
    {
        return new Widget($this->sku, 0);
    }

    protected function seed(): void
    {
        $this->sku = 'W-1';
    }
}

/**
 * Inherits a protected static $registry and a protected readonly $commissioned
 * from its base, both visible to this scope and neither writable by a clone.
 * Beacon requires both by name, so a rule asking only whether a property exists
 * would report full coverage over a builder that can vary neither.
 *
 * @extends AbstractStationBuilder<Beacon>
 */
#[CoversBuiltType]
final class BeaconBuilder extends AbstractStationBuilder
{
    private string $name;

    public function build(): Beacon
    {
        return new Beacon($this->name, self::$registry ?? '', $this->commissioned);
    }

    protected function seed(): void
    {
        $this->name = 'Sevastopol';
        $this->commission('2122');
    }
}

/**
 * Hand-rolled, so it never inherits the kernel. The promise still has to be
 * checked, because coverage compares properties against a constructor and needs
 * neither mutate() nor seed().
 *
 * @implements BuilderInterface<Widget>
 */
#[CoversBuiltType]
final class HandRolledWidgetBuilder implements BuilderInterface
{
    private string $sku = 'W-1';

    public static function create(): static
    {
        return new self();
    }

    public function build(): Widget
    {
        return new Widget($this->sku, 0);
    }
}

/**
 * @extends AbstractBuilder<string>
 */
#[CoversBuiltType]
final class ScalarBuilder extends AbstractBuilder
{
    private string $value;

    public function build(): string
    {
        return $this->value;
    }

    protected function seed(): void
    {
        $this->value = 'x';
    }
}

/**
 * @extends AbstractBuilder<Derelict>
 */
#[CoversBuiltType]
final class DerelictBuilder extends AbstractBuilder
{
    public function build(): Derelict
    {
        return new Derelict();
    }

    protected function seed(): void {}
}

/**
 * A union of two built types names no single constructed class, and no builder
 * could satisfy the required ingredients of both branches at once.
 *
 * @extends AbstractBuilder<Widget|Beacon>
 */
#[CoversBuiltType]
final class EitherBuilder extends AbstractBuilder
{
    private string $sku;

    public function build(): Widget|Beacon
    {
        return new Widget($this->sku, 0);
    }

    protected function seed(): void
    {
        $this->sku = 'W-1';
    }
}

/**
 * Unpromised and scalar-returning, so neither branch of the rule has anything to
 * say about it.
 *
 * @extends AbstractBuilder<string>
 */
final class UnpromisedScalarBuilder extends AbstractBuilder
{
    private string $value;

    public function build(): string
    {
        return $this->value;
    }

    protected function seed(): void
    {
        $this->value = 'x';
    }
}

/**
 * A base cannot make the promise for the builders under it, so saying so here is
 * reported instead of quietly checking nothing.
 *
 * @template-covariant T
 * @extends AbstractBuilder<T>
 */
#[CoversBuiltType]
abstract class AbstractPromisingBuilder extends AbstractBuilder
{
    protected string $sku = 'W-1';
}

/**
 * Extends a base that promised, and promises nothing itself, so it must be
 * silent even though it covers no $quantity.
 *
 * @extends AbstractPromisingBuilder<Widget>
 */
final class InheritedPromiseBuilder extends AbstractPromisingBuilder
{
    public function build(): Widget
    {
        return new Widget($this->sku, 0);
    }

    protected function seed(): void {}
}

/**
 * A built type whose constructor carries several signatures names no single set
 * of required ingredients. Reading one with selectSingle() throws inside PHPStan
 * and takes the whole file's analysis with it, so the count is guarded.
 *
 * @extends AbstractBuilder<ReflectionMethod>
 */
#[CoversBuiltType]
final class ReflectingBuilder extends AbstractBuilder
{
    private string $method;

    public function build(): ReflectionMethod
    {
        return new ReflectionMethod($this->method);
    }

    protected function seed(): void
    {
        $this->method = 'DateTime::format';
    }
}

/**
 * Hands back itself, so the rule would otherwise compare the builder against its
 * own constructor and pass on a promise about nothing.
 *
 * @implements BuilderInterface<SelfReturningBuilder>
 */
#[CoversBuiltType]
final class SelfReturningBuilder implements BuilderInterface
{
    public function __construct(private readonly string $sku = 'W-1') {}

    public static function create(): static
    {
        return new self();
    }

    public function build(): self
    {
        return $this;
    }
}

/**
 * Hand-rolled and readonly, replacing the whole instance per modifier rather than
 * writing a clone. Every ingredient is variable per test, so demanding a writable
 * slot of one would be a false accusation - presence is the whole question here.
 *
 * @implements BuilderInterface<Widget>
 */
#[CoversBuiltType]
final readonly class ReplacingWidgetBuilder implements BuilderInterface
{
    public function __construct(
        private string $sku = 'W-1',
        private int $quantity = 1,
    ) {}

    public static function create(): static
    {
        return new self();
    }

    public function withQuantity(int $quantity): self
    {
        return new self($this->sku, $quantity);
    }

    public function build(): Widget
    {
        return new Widget($this->sku, $this->quantity);
    }
}
