<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\Fixture;

use Pizgariu\ImmutableTestBuilder\Implementation\AbstractBuilder;

/**
 * Base with a PRIVATE ingredient on purpose - the concrete scope cannot see it,
 * so a map-form write against it must be refused, not silently shadowed. The
 * protected STATIC beside it is the shared-configuration shape an abstract base
 * is allowed to hold, and the concrete scope can see that one, which is exactly
 * why the map guard has to refuse it on its own rather than trusting visibility.
 *
 * @template-covariant T
 * @extends AbstractBuilder<T>
 */
abstract class AbstractStationBuilder extends AbstractBuilder
{
    protected static ?string $registry = null;

    private string $designation = 'Sevastopol';

    final protected function designation(): string
    {
        return $this->designation;
    }
}
