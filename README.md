# immutable-test-builder

Declare one valid object, once. Every test rents a tailored copy through modifiers that never touch the original. Drive a builder somewhere impossible and it refuses loudly instead of building a lie.

[![CI](https://github.com/pizgariu/immutable-test-builder/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/pizgariu/immutable-test-builder/actions/workflows/ci.yml)
[![Packagist](https://img.shields.io/packagist/v/pizgariu/immutable-test-builder.svg)](https://packagist.org/packages/pizgariu/immutable-test-builder)
[![PHP versions](https://img.shields.io/badge/php-8.3%20%7C%208.4%20%7C%208.5-blue.svg)](https://github.com/pizgariu/immutable-test-builder)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

Immutable test data builders for PHP. A builder is born with a perfect default: `build()` succeeds immediately, seeded with realistic faker data, so a test states only the values it asserts on. Every modifier returns a NEW instance, so a builder can sit in a shared fixture or serve as the trunk for divergent variants and no test corrupts another. One contract, one base class, one generator registry, one exception - `fakerphp/faker` is the only runtime dependency.

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

use Faker\Generator;
use Pizgariu\ImmutableTestBuilder\BaseBuilder;
use Pizgariu\ImmutableTestBuilder\Exception\UnbuildableState;

/**
 * @extends BaseBuilder<User>
 */
final class UserBuilder extends BaseBuilder
{
    private string $name;

    private ?string $email;

    /** @var list<string> */
    private array $roles;

    private bool $active;

    protected function seed(Generator $faker): void
    {
        $this->name = $faker->name();
        $this->email = $faker->safeEmail();
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

    public function appendRole(string $role): static
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

        $admin = $base->appendRole('admin');
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

**The default built immediately.** The first test puts nothing between `create()` and `build()`. Two of its assertions prove the seeded data is plausible - a non-empty name, a real-shaped email address - not that it equals any particular value; nobody typed those. The other two pin deliberate constants from `seed()` - one sensible role, an active account - because a perfect default mixes plausible randomness with opinionated fixed choices. Either way, no future test breaks because a required field was forgotten: the builder cannot exist without them.

**Three variants, one trunk, zero interference.** The second test tailors a base builder, then branches it twice. `appendRole()` gave the admin variant a second role, `asDeactivated()` switched the other variant off, and the assertions on the trunk still hold after both branches were taken: one role, active. Each modifier returned a new instance, so the trunk never learned about its descendants and the two branches never learned about each other.

**The impossible state refused to build.** `withoutEmail()` removes an ingredient `User` cannot exist without. `build()` does not hand back a broken object or a null - it throws `UnbuildableState`, and the asserted message names the builder, names the missing ingredient, and ends with the way out.

---

## Perfect default

The contract's first law: `create()` returns a builder that must `build()` successfully with no further calls. A concrete builder keeps that promise in one place, `seed()`, which fills every ingredient with realistic faker data and is called exactly once at creation.

The payoff is what tests stop saying. A test that fills every field before it can build drowns the one value it actually asserts on in noise, and every reader has to guess which lines matter. With a perfect default, a test states its assertion targets and nothing else; everything it stays silent about is already valid and plausibly random. Random beats hardcoded here for the same reason property-based testing works: a suite that only ever sees `'test'` and `0` keeps passing on code that would fall over on real-shaped data.

---

## Immutability via mutate()

`BaseBuilder` carries the whole engine in one method:

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

## Creation-time locale

`create()` seeds from the default locale (`en_US`). `createIn()` seeds from any other, and the locale is fixed for the builder's whole lifetime:

```php
$user = UserBuilder::create()->build();
$polishUser = UserBuilder::createIn('pl_PL')->build();
```

The locale is deliberately not a modifier. A `withLocale()` call after creation would arrive too late: `seed()` has already run, so every already-seeded value would still be in the old locale and only later writes would follow the new one - half an object in one language, half in another, and no way to see it in a green test. That whole class of stale-randomization bugs is unrepresentable here, because there is no moment at which a builder's locale can change.

`locale()` returns the locale a builder was created in. A project that wants a different default everywhere overrides one hook in its own base builder - and every `create()` call site follows:

```php
protected static function defaultLocale(): string
{
    return 'pl_PL';
}
```

An unknown locale does not silently produce default-locale data. `createIn('pl-PL')` - a typo for `pl_PL` - would quietly fall back to `en_US` inside Faker and keep every test green; `Fakers::locale()` refuses it instead with an `InvalidArgumentException` naming the rejected string.

Behind the scenes, `Fakers` memoizes one `Faker\Generator` per locale per process, so seeding stays cheap no matter how many builders a suite creates. `Fakers::flush()` drops the memoized instances when a test needs generator-level isolation.

---

## The naming DSL

Modifier names are a documented contract of this library, not a suggestion:

| Prefix | Meaning | Example |
| --- | --- | --- |
| `with*(value)` | sets a value | `withEmail('ripley@example.test')` |
| `without*()` | empties or nullifies a value | `withoutEmail()` |
| `as*()` | a semantic boolean or state transition | `asDeactivated()` |
| `from*(source)` | hydrates the builder from an existing object | `fromRegistrationRequest($request)` |
| `append*(item)` | adds to a collection without replacing it | `appendRole('admin')` |

Every modifier returns a new instance via `mutate()`, no exceptions.

The prefixes `set*`, `make*` and `add*` are never used. `set*` promises an in-place write, and nothing here writes in place - a `setName()` that returns a fresh instance is a name telling a lie. `add*` leaves open whether the collection is replaced or extended; `append*` commits to extending. `make*` says nothing about anything. Static enforcement of the DSL ships as a PHPStan rule set in 1.1.0; until then the table above is the contract.

---

## Install

```
composer require --dev pizgariu/immutable-test-builder
```

PHP 8.3+ (`^8.3`) and `fakerphp/faker` are all it needs.

---

## Versioning and roadmap

The project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html). The API surface described above is stable for 1.0.0.

- **1.1.0** - a PHPStan rule set enforcing the naming DSL, organized per abstraction type in `Class/`, `Method/` and `Property/` subdirectories, so a `setFoo()` on a builder fails analysis instead of code review.
- **2.0.0** - an entity integrity check, and a Rector set that removes trivial modifiers.

Locale re-switching is not on any roadmap - it is the bug this API exists to make unrepresentable. Collection helper utilities are out of scope as well; `append*` modifiers stay hand-written one-liners.

---

## Development

```
composer install
vendor/bin/phpunit
vendor/bin/phpstan analyse
```

PHPStan runs at level max over `src` and `tests`. CI validates `composer.json` strictly, then runs the suite and the analysis on PHP 8.3, 8.4 and 8.5, on every push to `master` and every pull request. `fail-fast` is off, so a break on one interpreter does not hide the others.

---

## License

Released under the MIT License. See [LICENSE](LICENSE).
