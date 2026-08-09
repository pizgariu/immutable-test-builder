<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Contract\Attribute;

use Attribute;

/**
 * Promises that the builder owns every required ingredient of the type it
 * builds, so a test can vary each one. BuiltTypeCoverageRule holds the builder
 * to that promise and stays silent on a builder that never made it.
 *
 *     #[CoversBuiltType]
 *     final class WidgetBuilder extends AbstractBuilder
 *
 * The promise is opt-in because only the author knows whether an ingredient is
 * missing or deliberately fixed. A builder that pins a field to one literal is
 * a legitimate fixture, so a rule that demanded coverage everywhere would be
 * stating a preference rather than checking a claim.
 *
 * That also makes it the annotation to add LAST. A builder for a wide type can
 * grow one ingredient at a time and promise coverage once it is complete, which
 * a project-wide switch would never allow.
 *
 * The promise is not inherited. Putting this on a shared abstract base would opt
 * in every builder beneath it, including ones nobody has finished, so each
 * builder says it for itself.
 *
 * The kernel ignores this attribute entirely, unlike Plural and NotMagic which
 * it reads. Without config/extension.neon nothing checks the promise.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class CoversBuiltType {}
