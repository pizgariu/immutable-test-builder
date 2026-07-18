# Changelog

All notable changes to immutable-test-builder are recorded here. This project follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) and [Semantic Versioning](https://semver.org/spec/v2.0.0.html). What a perfect default is, and why a builder refuses loudly instead of building a lie, lives in the [README](README.md).

## [Unreleased]

Nothing yet.

## [1.0.0] - 2026-07-18

### Added
- **The `Builder` contract: a perfect default in, a valid object or a loud refusal out.** Two methods are the whole promise. `create()` returns a builder that `build()`s successfully with no further calls, so a test states only the values it asserts on. `build()` returns the target object or throws `UnbuildableState` - it never hands back a half-thing. The interface is covariant over what it builds, so a `Builder<Admin>` passes wherever a `Builder<User>` is asked for.
- **`BaseBuilder`, the kernel every concrete builder extends.** `createIn()` fixes the locale and calls `seed()` exactly once, so the perfect default is filled with realistic faker data at birth and the seeding logic has exactly one home. `mutate()` is the immutability engine - clone, apply the change to the clone, return the clone - which makes every public modifier a one-liner and guarantees no builder instance is written after creation. The locale is creation-time only by design: a switchable locale would leave already-seeded values in the old one, a whole class of stale-randomization bugs this API refuses to represent.
- **`Fakers`, the memoized generator registry.** One `Faker\Generator` per locale per process, created on first ask and reused ever after, so seeding stays cheap no matter how many builders a suite creates. `flush()` drops the memoized instances for tests that reseed or replace generators, keeping generator-level isolation a one-call affair.
- **`UnbuildableState`, the loud failure.** A builder driven into an impossible state throws instead of producing a broken object that fails far from its cause. Two factories compose the two message shapes - `missing()` for an absent ingredient, `contradiction()` for calls that cancel each other out - and both name the builder by its short class name and end with concrete guidance, so the failing test points straight at the fix.
- **The example suite: living documentation under `tests/Example`.** A readonly `User`, the `UserBuilder` that produces it, and the test that proves the three guarantees: the perfect default builds immediately, branches from one shared trunk never interfere, and the guard message reads exactly as promised. The README prints the same files verbatim.

[Unreleased]: https://github.com/pizgariu/immutable-test-builder/compare/1.0.0...HEAD
[1.0.0]: https://github.com/pizgariu/immutable-test-builder/releases/tag/1.0.0
