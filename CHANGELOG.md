# Changelog

All notable changes to immutable-test-builder are recorded here. This project follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) and [Semantic Versioning](https://semver.org/spec/v2.0.0.html). What a perfect default is, and why a builder refuses loudly instead of building a lie, lives in the [README](README.md).

## [Unreleased]

Nothing yet.

## [1.0.0] - 2026-07-18

### Added
- **The `BuilderInterface` contract: a perfect default in, a valid object or a loud refusal out.** Two methods are the whole promise. `create()` returns a builder that `build()`s successfully with no further calls, so a test states only the values it asserts on. `build()` returns the target object or throws `UnbuildableState` - it never hands back a half-thing. The interface is covariant over what it builds, so a `BuilderInterface<Admin>` passes wherever a `BuilderInterface<User>` is asked for.
- **`AbstractBuilder`, the dependency-free kernel every concrete builder extends.** `create()` calls `seed()` exactly once, so the perfect default has exactly one home - and `seed()` is plain PHP, because the kernel imposes no randomness strategy: a constant, a `random_int()` suffix or a project-supplied generator are all equally welcome and none is required. `mutate()` is the immutability engine - clone, apply the change to the clone, return the clone - which makes every public modifier a one-liner and guarantees no builder instance is written after creation. Zero runtime dependencies.
- **A PHPStan rule set enforcing the DSL, bundled from day one.** Eight rules, one directory per abstraction type. `Rule/Class` seals concrete builders as final. `Rule/Method` restricts the public surface to the DSL, requires every modifier to be a static-returning one-liner through `mutate()`, keeps `seed()` protected and free of modifier or `build()` calls (the returned clone would be silently discarded), demands a concrete non-nullable return type on `build()` and refuses non-static mutation closures - a non-static closure keeps `$this` bound to the original builder and could mutate the trunk behind `mutate()`'s back. `Rule/Property` keeps builder state private, per-instance and never readonly, and requires every property to carry an inline default or a direct assignment in `seed()` - the per-property face of the perfect default. One `includes:` line for `extension.neon` turns the contract into analysis errors; PHPStan itself stays an optional `suggest`.
- **`UnbuildableState`, the loud failure.** A builder driven into an impossible state throws instead of producing a broken object that fails far from its cause. Two factories compose the two message shapes - `missing()` for an absent ingredient, `contradiction()` for calls that cancel each other out - and both name the builder by its short class name and end with concrete guidance, so the failing test points straight at the fix.
- **The example suite: living documentation under `tests/Example`.** A readonly `User`, the `UserBuilder` that produces it, and the test that proves the three guarantees: the perfect default builds immediately, branches from one shared trunk never interfere, and the guard message reads exactly as promised. The README prints the same files verbatim.

[Unreleased]: https://github.com/pizgariu/immutable-test-builder/compare/1.0.0...HEAD
[1.0.0]: https://github.com/pizgariu/immutable-test-builder/releases/tag/1.0.0
