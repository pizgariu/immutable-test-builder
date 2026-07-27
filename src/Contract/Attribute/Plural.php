<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Contract\Attribute;

use Attribute;

/**
 * Declares the singular a collection property answers to, for the cases the
 * naive plural cannot reach. including*() and excluding*() derive a singular
 * stem from the method name and look for that stem plus a simple +s plural. An
 * irregular plural needs this bridge.
 *
 *     #[Plural(of: 'person')]
 *     private array $people;
 *
 * makes includingPerson() and excludingPerson() resolve to $people. The
 * argument is the singular stem - the plural is already the property name.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Plural
{
    public function __construct(public string $of) {}
}
