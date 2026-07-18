# immutable-test-builder

Declare one valid object, once. Every test rents a tailored copy through modifiers that never touch the original. Drive a builder somewhere impossible and it refuses loudly instead of building a lie.

[![CI](https://github.com/pizgariu/immutable-test-builder/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/pizgariu/immutable-test-builder/actions/workflows/ci.yml)
[![Packagist](https://img.shields.io/packagist/v/pizgariu/immutable-test-builder.svg)](https://packagist.org/packages/pizgariu/immutable-test-builder)
[![PHP versions](https://img.shields.io/badge/php-8.3%20%7C%208.4%20%7C%208.5-blue.svg)](https://github.com/pizgariu/immutable-test-builder)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

Immutable test data builders for PHP. A builder is born with a perfect default: `build()` succeeds immediately, seeded with realistic faker data, so a test states only the values it asserts on. Every modifier returns a NEW instance, so a builder can sit in a shared fixture or serve as the trunk for divergent variants and no test corrupts another. One contract, one abstract base, one exception and a PHPStan rule set that polices the DSL. Pure standard library, zero runtime dependencies.

---

## See it work

This is the example suite under [`tests/Example`](tests/Example), printed verbatim: a readonly value object, the builder that produces it, and the test that proves the guarantees.

```php
<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\Example;

/**
 * Value object produced by UserBuilder; part of the library's living documentation.
 */
final readonly class User
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        public string $name,
        public string $email,
        public array $roles,
        public bool $active,
    ) {
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\Example;

use Pizgariu\ImmutableTestBuilder\AbstractBuilder;
use Pizgariu\ImmutableTestBuilder\Exception\UnbuildableState;

/**
 * @extends AbstractBuilder<User>
 */
final class UserBuilder extends AbstractBuilder
{
    private string $name;

    private ?string $email;

    /** @var list<string> */
    private array $roles;

    private bool $active;

    protected function seed(): void
    {
        $suffix = random_int(1, 9999);

        $this->name = sprintf('User %04d', $suffix);
        $this->email = sprintf('user-%04d@example.test', $suffix);
        $this->roles = ['user'];
        $this->active = true;
    }

    public function withName(string $name): static
    {
        return $this->mutate(static function (self $builder) use ($name): void {
            $builder->name = $name;
        });
    }

    public function withEmail(string $email): static
    {
        return $this->mutate(static function (self $builder) use ($email): void {
            $builder->email = $email;
        });
    }

    public function withoutEmail(): static
    {
        return $this->mutate(static function (self $builder): void {
            $builder->email = null;
        });
    }

    public function asDeactivated(): static
    {
        return $this->mutate(static function (self $builder): void {
            $builder->active = false;
        });
    }

    public function includingRole(string $role): static
    {
        return $this->mutate(static function (self $builder) use ($role): void {
            $builder->roles[] = $role;
        });
    }

    /**
     * @throws UnbuildableState when the email address was removed
     */
    public function build(): User
    {
        if (null === $this->email) {
            throw UnbuildableState::missing(
                self::class,
                'an email address',
                'Call withEmail() or drop withoutEmail().',
            );
        }

        return new User($this->name, $this->email, $this->roles, $this->active);
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\Example;

use PHPUnit\Framework\TestCase;
use Pizgariu\ImmutableTestBuilder\Exception\UnbuildableState;

final class UserBuilderTest extends TestCase
{
    public function testCreateReturnsBuilderThatBuildsImmediately(): void
    {
        $user = UserBuilder::create()->build();

        self::assertNotSame('', $user->name);
        self::assertStringContainsString('@', $user->email);
        self::assertSame(['user'], $user->roles);
        self::assertTrue($user->active);
    }

    public function testBranchingFromSharedBaseLeavesEveryVariantIndependent(): void
    {
        $base = UserBuilder::create()
            ->withName('Ellen Ripley')
            ->withEmail('ripley@example.test');

        $admin = $base->includingRole('admin');
        $deactivated = $base->asDeactivated();

        $baseUser = $base->build();
        $adminUser = $admin->build();
        $deactivatedUser = $deactivated->build();

        self::assertSame(['user'], $baseUser->roles);
        self::assertTrue($baseUser->active);
        self::assertSame(['user', 'admin'], $adminUser->roles);
        self::assertTrue($adminUser->active);
        self::assertSame(['user'], $deactivatedUser->roles);
        self::assertFalse($deactivatedUser->active);
        self::assertSame('Ellen Ripley', $baseUser->name);
        self::assertSame('ripley@example.test', $adminUser->email);
        self::assertSame('ripley@example.test', $deactivatedUser->email);
    }

    public function testBuildThrowsWhenEmailWasRemoved(): void
    {
        $builder = UserBuilder::create()->withoutEmail();

        $this->expectException(UnbuildableState::class);
        $this->expectExceptionMessage(
            'UserBuilder cannot build yet - missing an email address. Call withEmail() or drop withoutEmail().',
        );

        $builder->build();
    }
}
```

Three things in that test are the entire idea.

**The default built immediately.** The first test puts nothing between `create()` and `build()`. The name and the email are generated in `seed()` around one random suffix, so every default user is complete and unique without any test typing them - the assertions check shape, not exact strings. The other two assertions pin deliberate constants - one sensible role, an active account - because a perfect default mixes generated uniqueness with opinionated fixed choices. Either way, no future test breaks because a required field was forgotten: the builder cannot exist without them.

**Three variants, one trunk, zero interference.** The second test tailors a base builder, then branches it twice. `includingRole()` gave the admin variant a second role, `asDeactivated()` switched the other variant off, and the assertions on the trunk still hold after both branches were taken: one role, active. Each modifier returned a new instance, so the trunk never learned about its descendants and the two branches never learned about each other.

**The impossible state refused to build.** `withoutEmail()` removes an ingredient `User` cannot exist without. `build()` does not hand back a broken object or a null - it throws `UnbuildableState`, and the asserted message names the builder, names the missing ingredient, and ends with the way out.

---

## Perfect default

The contract's first law: `create()` returns a builder that must `build()` successfully with no further calls. A concrete builder keeps that promise in one place, `seed()`, which fills every ingredient with a realistic default and is called exactly once at creation.

The payoff is what tests stop saying. A test that fills every field before it can build drowns the one value it actually asserts on in noise, and every reader has to guess which lines matter. With a perfect default, a test states its assertion targets and nothing else; everything it stays silent about is already valid. How a default is produced is deliberately outside the kernel: a constant, a `random_int()` suffix, a full data generator - `seed()` is plain PHP and the choice stays with the project.

---

## Immutability via mutate()

`AbstractBuilder` carries the whole engine in one method:

```php
final protected function mutate(Closure $mutation): static
{
    $clone = clone $this;
    $mutation($clone);

    return $clone;
}
```

Clone, apply the change to the clone, return the clone. Every public modifier of a concrete builder is a one-liner delegating here:

```php
public function withName(string $name): static
{
    return $this->mutate(static function (self $builder) use ($name): void {
        $builder->name = $name;
    });
}
```

After `seed()` runs, no builder instance is ever written again. Two guarantees follow. A builder held in a shared fixture, a class property or a helper method cannot be corrupted by one test on behalf of the next, because no call site can mutate it. And a partially tailored builder can serve as the trunk for several divergent variants inside a single test - the branching test above - with none of the variants observing the others.

One boundary to know about: the clone is shallow. Isolation holds for scalar, array and immutable-object ingredients; a mutable object ingredient (an entity, an `ArrayObject`) is shared between trunk and branches. Replace such an ingredient inside the modifier instead of mutating it in place, or deep-copy it in an overridden `__clone()`.

When PHP 8.5's clone-with syntax becomes this package's floor, `mutate()` swaps its internals for the expression form - the signature and the contract stay put.

---

## Loud failure

A builder driven into an impossible state never produces a broken object. `build()` throws `UnbuildableState` (a `LogicException`), composed by one of two factories with a fixed message shape each:

```
<ShortName> cannot build yet - missing <ingredient>. <advice>
<ShortName> was driven into a contradiction - <conflict>. <wayOut>
```

`missing()` is for an ingredient that is absent, `contradiction()` for two calls that cancel each other out. Both name the offending builder by its short class name and end with concrete guidance, so the failing test points straight at the fix:

```
UserBuilder cannot build yet - missing an email address. Call withEmail() or drop withoutEmail().
OrderBuilder was driven into a contradiction - asPaid() combined with withoutPayment(). Drop one of the two calls.
```

The alternative is worse than a crash: a builder that quietly hands back a half-valid object moves the failure into whatever code touches the object next, far from the line that caused it.

---

## Bring your own randomness

The kernel ships zero runtime dependencies and imposes no randomness strategy. `seed()` is plain PHP: the example above builds its perfect default around one `random_int()` suffix, and that is all the uniqueness most suites need.

A project that wants richer generated data plugs its own generator in through its own abstract base - the kernel never knows:

```php
use Faker\Factory;
use Faker\Generator;
use Pizgariu\ImmutableTestBuilder\AbstractBuilder;

abstract class ProjectBuilder extends AbstractBuilder
{
    private static ?Generator $faker = null;

    final protected static function faker(): Generator
    {
        return self::$faker ??= Factory::create('pl_PL');
    }
}
```

Concrete builders extend `ProjectBuilder` and call `static::faker()` inside `seed()`. The generator library (`fakerphp/faker` above) lives in the project's own `require-dev`, not in this package - swapping it, localizing it or dropping it never touches the kernel.

---

## The naming DSL

Modifier names are a documented contract of this library, not a suggestion:

| Prefix | Meaning | Example |
| --- | --- | --- |
| `with*(value)` | sets a value | `withEmail('ripley@example.test')` |
| `without*()` | empties or nullifies a value | `withoutEmail()` |
| `as*()` | a semantic boolean or state transition | `asDeactivated()` |
| `from*(source)` | hydrates the builder from an existing object | `fromRegistrationRequest($request)` |
| `for*(owner)` | establishes context or ownership | `forCustomer($customer)` |
| `including*(item)` | adds to a collection without replacing it | `includingRole('admin')` |
| `excluding*(item)` | removes from a collection without replacing it | `excludingRole('guest')` |
| `having*(...)` | atomic mutation of one inseparable domain concept | `havingAge(18)` |

Every modifier returns a new instance via `mutate()`, no exceptions.

The prefixes `set*`, `make*` and `add*` are never used. `set*` promises an in-place write, and nothing here writes in place - a `setName()` that returns a fresh instance is a name telling a lie. `add*` leaves open whether the collection is replaced or extended; `including*` commits to extending and `excluding*` to shrinking. `make*` says nothing about anything. The table is not a style guide waiting for review vigilance - the bundled PHPStan rule set turns it into analysis errors; the next section shows how.

---

## Enforced by PHPStan

The package bundles a PHPStan rule set that enforces the DSL on every class implementing `BuilderInterface`. One include turns it on:

```neon
includes:
    - vendor/pizgariu/immutable-test-builder/extension.neon
```

Eight rules, one directory per abstraction type:

| Rule | Lives in | What it refuses |
| --- | --- | --- |
| `FinalBuilderRule` | `Rule/Class` | a concrete builder that is not final - a builder is a leaf, extension points belong in an abstract base |
| `ModifierNameRule` | `Rule/Method` | a public method outside the DSL - and `set*`, `make*`, `add*` each get a message explaining why the name lies |
| `ModifierDelegatesToMutateRule` | `Rule/Method` | a modifier that is not a static-returning one-liner through `mutate()` - the whole immutability proof in one shape check |
| `SeedDisciplineRule` | `Rule/Method` | a public `seed()` (re-seeding a live builder is mutation through the back door) and any `seed()` that calls a modifier or `build()` - the returned clone would be silently thrown away |
| `BuildReturnTypeRule` | `Rule/Method` | a `build()` without a concrete non-nullable return type - an impossible state throws `UnbuildableState`, it never leaks out as null or mixed |
| `StaticMutationClosureRule` | `Rule/Method` | a non-static mutation closure - it keeps `$this` bound to the original builder, and one `$this->` write inside would mutate the trunk behind `mutate()`'s back |
| `WritableStateRule` | `Rule/Property` | builder state that is not private, is static, or is readonly - readonly state would make `mutate()` throw at runtime |
| `PerfectDefaultPropertyRule` | `Rule/Property` | a property with neither an inline default nor a direct assignment in `seed()` - the per-property face of the perfect default promise |

Abstract bases are exempt where it matters: they may hold immutable configuration, like the memoized project generator above, without tripping the property rule. PHPStan itself stays optional - it sits in `suggest`, and without it the package is just the kernel.

---

## Install

```
composer require --dev pizgariu/immutable-test-builder
```

PHP 8.3+ (`^8.3`). Nothing else - the package has zero runtime dependencies.

---

## Versioning and roadmap

The project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html). The API surface described above is stable for 1.0.0.

- **2.0.0** - an entity integrity check, and a Rector set that removes trivial modifiers.

A built-in data generator is not on any roadmap - randomness stays the project's choice, plugged in through `seed()`. Collection helper utilities are out of scope as well; `including*` and `excluding*` modifiers stay hand-written one-liners.

---

## Development

```
composer install
vendor/bin/phpunit
vendor/bin/phpstan analyse
```

PHPStan runs at level max over `src` and `tests`, with the bundled rule set included - the package dogfoods its own DSL. CI validates `composer.json` strictly, then runs the suite and the analysis on PHP 8.3, 8.4 and 8.5, on every push to `master` and every pull request. `fail-fast` is off, so a break on one interpreter does not hide the others.

---

## License

Released under the MIT License. See [LICENSE](LICENSE).
