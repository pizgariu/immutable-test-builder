<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\Fixture;

use Pizgariu\ImmutableTestBuilder\Implementation\AbstractBuilder;

/**
 * Base with a PRIVATE ingredient on purpose - the concrete scope cannot see it,
 * so a map-form write against it must be refused, not silently shadowed. The
 * protected STATIC and the protected READONLY beside it are shapes an abstract
 * base is allowed to hold, since WritableStateRule exempts abstract classes, and
 * the concrete scope can see both. That is exactly why the derivation and the map
 * guard have to refuse them on their own rather than trusting that a rule ran.
 *
 * @template-covariant T
 * @extends AbstractBuilder<T>
 */
abstract class AbstractStationBuilder extends AbstractBuilder
{
    protected static ?string $registry = null;

    // @phpstan-ignore property.uninitializedReadonly (a builder cannot declare a constructor, so PHPStan has nowhere it will accept this being assigned - the point of the fixture)
    protected readonly string $commissioned;

    private string $designation = 'Sevastopol';

    final protected function designation(): string
    {
        return $this->designation;
    }

    /**
     * A readonly ingredient cannot carry a default and cannot be assigned from
     * the concrete scope, and a builder cannot declare a constructor because the
     * kernel seals it. An initialiser here is the only shape PHP leaves, and
     * PHPStan rejects even that, so readonly builder state is a thing the runtime
     * permits and no analyser will bless. That is precisely why the kernel refuses
     * to write one instead of letting PHP raise a bare Error mid-mutation.
     */
    final protected function commission(string $year): void
    {
        // @phpstan-ignore property.readOnlyAssignNotInConstructor (PHP permits this once, PHPStan does not, and the gap between them is what the kernel guard covers)
        $this->commissioned = $year;
    }
}
