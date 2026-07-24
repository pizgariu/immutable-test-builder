<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Contract\Attribute;

use Attribute;

/**
 * Seals a property from magic derivation. The kernel does not derive a
 * modifier for a property carrying this attribute, so the runtime refuses the
 * call and the analysis reports an undefined method - a computed or reserved
 * ingredient stays reachable only through a handwritten modifier.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class NotMagic {}
