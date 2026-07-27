<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\Fixture;

use Pizgariu\ImmutableTestBuilder\Implementation\AbstractBuilder;

/**
 * Base with a PRIVATE ingredient on purpose - the concrete scope cannot see it,
 * so a map-form write against it must be refused, not silently shadowed.
 *
 * @template-covariant T
 * @extends AbstractBuilder<T>
 */
abstract class AbstractStationBuilder extends AbstractBuilder
{
    private string $designation = 'Sevastopol';

    final protected function designation(): string
    {
        return $this->designation;
    }
}
