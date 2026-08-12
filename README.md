# Immutable Test Builder

Declare one valid object, once. Every test rents a tailored copy through modifiers that never touch the original. Drive a builder somewhere impossible and it refuses loudly instead of building a lie.

[![CI](https://github.com/pizgariu/immutable-test-builder/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/pizgariu/immutable-test-builder/actions/workflows/ci.yml)
[![Packagist](https://img.shields.io/packagist/v/pizgariu/immutable-test-builder.svg)](https://packagist.org/packages/pizgariu/immutable-test-builder)
[![PHP versions](https://img.shields.io/badge/php-8.3%20%7C%208.4%20%7C%208.5-blue.svg)](https://github.com/pizgariu/immutable-test-builder)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

Immutable test data builders for PHP. A builder is born with a perfect default - `build()` succeeds immediately, seeded with realistic generated data, so a test states only the values it asserts on. Every modifier returns a NEW instance, so a builder can sit in a shared fixture or serve as the trunk for divergent variants and no test corrupts another. One contract, one abstract base, one exception and a PHPStan rule set that polices how a builder must be written. Pure standard library, zero runtime dependencies.

---

## See it work

This is the example suite under [`tests/Example`](tests/Example), printed verbatim - a readonly value object, the builder that produces it, and the test that proves the guarantees.

```php
<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\Example;

/**
 * Value object produced by UserBuilder. Part of the library's living documentation.
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
    ) {}
}
```

```php
<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\Example;

use Pizgariu\ImmutableTestBuilder\Contract\Exception\UnbuildableState;
use Pizgariu\ImmutableTestBuilder\Implementation\AbstractBuilder;

/**
 * withName(), withEmail(), withoutEmail() and includingRole() are never
 * written here - the kernel derives them from the property declarations.
 * Only the meaningful modifier has a body - asDeactivated() flips $active,
 * a property no prefix could guess from the method name.
 *
 * PHPStan types every derived call through the bundled extension. An IDE
 * does not read PHPStan extensions, so autocomplete wants @method tags on
 * the builder - and the bundled Rector set maintains them, replacing every
 * modifier the kernel already derives with its tag.
 *
 * @method UserBuilder withName(string $name)
 * @method UserBuilder withEmail(?string $email)
 * @method UserBuilder withoutEmail()
 * @method UserBuilder includingRole(string $role)
 *
 * @extends AbstractBuilder<User>
 */
final class UserBuilder extends AbstractBuilder
{
    private string $name;

    private ?string $email;

    /** @var list<string> */
    private array $roles;

    private bool $active;

    public function asDeactivated(): static
    {
        return $this->mutate(static function (self $builder): void {
            $builder->active = false;
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

    protected function seed(): void
    {
        $suffix = random_int(1, 9999);

        $this->name = sprintf('User %04d', $suffix);
        $this->email = sprintf('user-%04d@example.test', $suffix);
        $this->roles = ['user'];
        $this->active = true;
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\Example;

use PHPUnit\Framework\TestCase;
use Pizgariu\ImmutableTestBuilder\Contract\Exception\UnbuildableState;

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
            ->withEmail('ripley@example.test')
        ;

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

**The default built immediately.** The first test puts nothing between `create()` and `build()`. The name and the email are generated in `seed()` around one random suffix, so every default user is complete and unique without any test typing them - the assertions check shape, not exact strings. The other two assertions pin deliberate constants - one sensible role, an active account - because a perfect default mixes generated uniqueness with opinionated fixed choices. Either way, no future test breaks because a required field was forgotten. The builder cannot exist without them.

**Three variants, one trunk, zero interference.** The second test tailors a base builder, then branches it twice. `includingRole()` gave the admin variant a second role, `asDeactivated()` switched the other variant off, and the assertions on the trunk still hold after both branches were taken - one role, active. Each modifier returned a new instance, so the trunk never learned about its descendants and the two branches never learned about each other. And look back at the builder - `withName()`, `withEmail()` and `includingRole()` are called here yet declared nowhere. The kernel derived them from `$name`, `$email` and `$roles`. Only `asDeactivated()` has a body, because no property could tell the engine that deactivating means `$active = false`.

**The impossible state refused to build.** `withoutEmail()` removes an ingredient `User` cannot exist without. `build()` does not hand back a broken object or a null - it throws `UnbuildableState`, and the asserted message names the builder, names the missing ingredient, and ends with the way out.

---

## Perfect default

The contract's first law is that `create()` returns a builder that must `build()` successfully with no further calls. A concrete builder keeps that promise in one place, `seed()`, which fills every ingredient with a realistic default and is called exactly once at creation.

The payoff is what tests stop saying. A test that fills every field before it can build drowns the one value it actually asserts on in noise, and every reader has to guess which lines matter. With a perfect default, a test states its assertion targets and nothing else. Everything it stays silent about is already valid. How a default is produced is deliberately outside the kernel. A constant, a `random_int()` suffix, a full data generator - `seed()` is plain PHP and the choice stays with the project.

---

## Immutability via mutate()

`AbstractBuilder` carries the immutability engine in one method.

```php
final protected function mutate(Closure|array $mutation): static
{
    $clone = clone $this;

    if ($mutation instanceof Closure) {
        $mutation($clone);

        return $clone;
    }

    /** @var array<class-string, array<string, true>> $writable */
    static $writable = [];

    $names = $writable[static::class] ?? null;

    if (null === $names) {
        $names = [];

        foreach ((new ReflectionClass(static::class))->getProperties() as $property) {
            if (!$property->isStatic() && !$property->isReadOnly()) {
                $names[$property->name] = true;
            }
        }

        $writable[static::class] = $names;
    }

    Closure::bind(static function (object $target) use ($mutation, $names): void {
        foreach ($mutation as $property => $value) {
            if (!isset($names[$property])) {
                throw new BadMethodCallException(sprintf(
                    'mutate() on %s cannot write $%s - the concrete scope sees no writable instance property under that name. Fix the key, declare shared base state protected so the bound write can reach it, or drop the static or readonly, since a clone owns neither.',
                    $target::class,
                    $property,
                ));
            }

            $target->{$property} = $value;
        }
    }, null, static::class)($clone);

    return $clone;
}
```

Clone, apply the change to the clone, return the clone - and refuse a map key that names no property the concrete scope can write, so a typo never becomes a silent dynamic property. Every public modifier of a concrete builder is a one-liner delegating here.

```php
public function withName(string $name): static
{
    return $this->mutate(static function (self $builder) use ($name): void {
        $builder->name = $name;
    });
}
```

A trivial write can skip the closure entirely - hand mutate() the property map instead.

```php
public function withCrew(int $crew): static
{
    return $this->mutate(['crew' => $crew]);
}
```

After `seed()` runs, no builder instance is ever written again. Two guarantees follow. A builder held in a shared fixture, a class property or a helper method cannot be corrupted by one test on behalf of the next, because no call site can mutate it. And a partially tailored builder can serve as the trunk for several divergent variants inside a single test - the branching test above - with none of the variants observing the others.

One boundary is worth knowing. The clone is shallow, so isolation holds for scalar, array and immutable-object ingredients. A mutable object ingredient (an entity, an `ArrayObject`) is shared between trunk and branches. Replace such an ingredient inside the modifier instead of mutating it in place, or deep-copy it in an overridden `__clone()`.

The property-map form already speaks PHP 8.5's clone-with dialect, portable back to 8.3 through a write bound into the concrete class scope. When 8.5 becomes this package's floor, `mutate()` swaps its internals for the native call and no call site moves.

> **Note:** The engine deliberately stops at a shallow clone instead of attempting a generic deep copy. A generic deep cloner would destroy the performance of a modifier chain and risk circular reference crashes on complex entity graphs. Keeping the kernel's clone shallow and fast means the CPU cost of copying heavy mutable state is never forced onto simple builders. When a specific builder does hold mutable objects or collections that need isolation, the project stays in control - you handle it explicitly by overriding `__clone()` in that concrete builder. Explicit overrides implicit, keeping the engine fast and predictable.
---

## Loud failure

A builder driven into an impossible state never produces a broken object. `build()` throws `UnbuildableState` (a `LogicException`), composed by one of two factories with a fixed message shape each.

```
<ShortName> cannot build yet - missing <ingredient>. <advice>
<ShortName> was driven into a contradiction - <conflict>. <wayOut>
```

`missing()` is for an ingredient that is absent, `contradiction()` for two calls that cancel each other out. Both name the offending builder by its short class name and end with concrete guidance, so the failing test points straight at the fix.

```
UserBuilder cannot build yet - missing an email address. Call withEmail() or drop withoutEmail().
OrderBuilder was driven into a contradiction - asPaid() combined with withoutPayment(). Drop one of the two calls.
```

The alternative is worse than a crash. A builder that quietly hands back a half-valid object moves the failure into whatever code touches the object next, far from the line that caused it.

---

## Bring your own randomness

The kernel ships zero runtime dependencies and imposes no randomness strategy. `seed()` is plain PHP, and the example above builds its perfect default around one `random_int()` suffix, which is all the uniqueness most suites need.

A project that wants richer generated data plugs its own generator in through its own abstract base - the kernel never knows.

```php
use Faker\Factory;
use Faker\Generator;
use Pizgariu\ImmutableTestBuilder\Implementation\AbstractBuilder;

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

Modifier names are a documented contract of this library, not a suggestion.

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

The prefixes `set*`, `make*` and `add*` are never used. `set*` promises an in-place write, and nothing here writes in place - a `setName()` that returns a fresh instance is a name telling a lie. `add*` leaves open whether the collection is replaced or extended, while `including*` commits to extending and `excluding*` to shrinking. `make*` says nothing about anything. The table is not a style guide waiting for review vigilance - the bundled PHPStan rule set turns it into analysis errors, and the next sections show how.

---

## Magic modifiers

The trivial modifiers do not exist as code. `__call` and the `Prefix` enum implement five of the eight prefixes straight from the property declarations.

| Prefix | Derived behaviour |
| --- | --- |
| `with*(value)` | assigns the argument to the matching property |
| `without*()` | assigns the inferred empty value - `null` for nullable, `[]`, `''`, `0`, `0.0`, `false` by type |
| `as*(bool\|null = true)` | sets the matching boolean flag - `asArmed()` raises it, `asArmed(false)` lowers it, `asMothballed(null)` clears a nullable one |
| `including*(item)` | appends with `[]=`, resolving the simple plural (`includingRole` writes `$roles`) |
| `excluding*(item)` | filters the item out, resolving the same plural |

Two of the package's three attributes tune the derivation when a name cannot carry the whole story. `#[Plural(of: 'person')]` on a collection property teaches the simple-plural resolver an irregular one - `includingPerson()` then reaches `$people`. `#[NotMagic]` on a property seals it from derivation entirely, so a computed or reserved ingredient is reachable only through a handwritten modifier. Both are read by the runtime and by the PHPStan extension from the same source, so the sealed method is a runtime refusal and an undefined-method analysis error alike.

`from*`, `for*` and `having*` are never magic - hydration, ownership and multi-property concepts deserve a handwritten body.

```php
public function fromApplicant(Applicant $applicant): static
{
    return $this->mutate(static function (self $builder) use ($applicant): void {
        $builder->email = $applicant->email;
        $builder->firstName = $applicant->firstName;
        $builder->lastName = $applicant->lastName;
    });
}

public function forAccount(Account $account): static
{
    return $this->mutate(['accountId' => $account->id]);
}

public function havingName(string $firstName, string $lastName): static
{
    return $this->mutate(['firstName' => $firstName, 'lastName' => $lastName]);
}
```

The full grammar, magic and handwritten side by side, lives in [`tests/Example/MembershipBuilder`](tests/Example/MembershipBuilder.php) with a test that exercises every prefix. A declared method always wins, because the engine only answers when no method exists. `asDeactivated()` earlier is handwritten precisely because no `$deactivated` property could tell the kernel what deactivating means.

Sealed state stays sealed. The magic writer is bound into the concrete class scope with `Closure::bind`, so properties remain private and every derived modifier still funnels through `mutate()` - same clone, same isolation, same branching guarantees. A call outside the contract fails loudly with `BadMethodCallException` and the way out - unknown prefix, a prefix that is never magic, a missing property, the wrong arity, named arguments (magic reads its value positionally), a flag that is not `bool`, a collection that is not `array`, or an empty value that cannot be inferred.

And the types hold. The bundled `MagicModifierMethodsExtension`, registered by the same `config/extension.neon`, teaches PHPStan every derived signature from the same `Prefix` semantics - `->withName('x')` analyses at level max with zero annotations and no mapper. It also never advertises a modifier the writers would refuse on the property type, so `asCargo()` on an `int` is an undefined method for analysis exactly as it is a refusal at runtime.

Your IDE is a different reader. It does not consult PHPStan extensions, so autocomplete for the derived modifiers comes from `@method` tags on the builder class - and the bundled Rector set writes and maintains them, as [its own section](#maintained-by-rector) shows.

---

## Enforced by PHPStan

The package bundles a PHPStan rule set that enforces the DSL on every class implementing `BuilderInterface`. One include turns it on. Nine of the ten fire on sight. The tenth waits to be asked, for a reason worth reading below.

```neon
includes:
    - vendor/pizgariu/immutable-test-builder/config/extension.neon
```

Ten rules, one directory per abstraction type.

| Rule | Lives in | What it refuses |
| --- | --- | --- |
| `BuiltTypeCoverageRule` | `Rule/Class` | on a builder that promised coverage, a required constructor parameter of the built type it owns no writable property for - and a promise it cannot check at all, since silence would read as coverage |
| `FinalBuilderRule` | `Rule/Class` | a concrete builder that is not final - a builder is a leaf, extension points belong in an abstract base |
| `ModifierNameRule` | `Rule/Method` | a public method outside the DSL - and `set*`, `make*`, `add*` each get a message explaining why the name lies |
| `ModifierBehaviourRule` | `Rule/Method` | a body that breaks its prefix's promise - `without*` taking parameters, `as*` declaring anything but one optional bool, `without*` assigning real values, `including*` that never appends, `excluding*` that appends, `having*` that writes a single property |
| `ModifierDelegatesToMutateRule` | `Rule/Method` | a modifier that is not a static-returning one-liner through `mutate()` - the whole immutability proof in one shape check |
| `SeedDisciplineRule` | `Rule/Method` | a public `seed()` (re-seeding a live builder is mutation through the back door) and any `seed()` that calls a modifier or `build()` - the returned clone would be silently thrown away |
| `BuildReturnTypeRule` | `Rule/Method` | a `build()` without a concrete non-nullable return type - an impossible state throws `UnbuildableState`, it never leaks out as null or mixed |
| `StaticMutationClosureRule` | `Rule/Method` | a non-static mutation closure - it keeps `$this` bound to the original builder, and one `$this->` write inside would mutate the trunk behind `mutate()`'s back |
| `WritableStateRule` | `Rule/Property` | builder state that is not private, is static, or is readonly - readonly state would make `mutate()` throw at runtime |
| `PerfectDefaultPropertyRule` | `Rule/Property` | a property with neither an inline default nor a direct assignment in `seed()` - the per-property face of the perfect default promise |

Abstract bases are exempt where it matters, so they may hold immutable configuration, like the memoized project generator above, without tripping the property rule. `BuiltTypeCoverageRule` says nothing at all until a builder invites it, which the section below explains. The rules police what you write by hand - a derived modifier is correct by construction, because the kernel implements each prefix's semantics exactly once. PHPStan itself stays optional - it sits in `suggest`, and without it the package is just the kernel.

The rule set needs nothing beyond `phpstan/phpstan` itself - the phar ships its own php-parser, and the `nikic/php-parser` pin in this package's `require-dev` only keeps development of the rules on the 5.x node shapes. The builders have no requirement at all - the kernel stays zero-dependency and runs anywhere on PHP 8.3+.

### The one rule that waits to be asked

A builder pinning an ingredient to a single literal still builds a complete object. Whether that is a gap or a deliberate fixture is something only its author knows, so a rule that demanded coverage everywhere would be stating a preference. `#[CoversBuiltType]` turns it into a claim the builder makes about itself, which the rule then verifies.

```php
#[CoversBuiltType]
final class MembershipBuilder extends AbstractBuilder
```

That reads as a promise to own every required ingredient of `Membership`, so a test can vary each one. Add a required field to `Membership` and the builder is told, by name, which ingredient it cannot write. Leave the attribute off and nothing is said, which is also what makes it the annotation to add LAST - a builder for a wide type grows one ingredient at a time and promises coverage once it is complete. A project-wide switch would demand all of them on the first day.

Coverage means a property the builder can actually write, the same question `mutate()` asks. State inherited from an abstract base that is `static` or `readonly` is visible to the concrete scope and unwritable by a clone, so it never counts, and a promise the rule cannot check at all is reported rather than passed in silence. The promise is not inherited either, so putting the attribute on a shared base cannot opt in builders nobody has finished.

### One more include, for cost rather than contract

Every rule above states a term of the contract, so breaking one means the builder is wrong. There is an eleventh rule that only says a builder is expensive, which is a different claim, so it ships apart and a project turns it on deliberately.

```neon
includes:
    - vendor/pizgariu/immutable-test-builder/config/performance.neon
```

| Rule | Lives in | What it refuses |
| --- | --- | --- |
| `CostlyCallInSeedRule` | `Rule/Performance` | `password_hash()` or `crypt()` inside `seed()`, plus whatever the project declares |

`seed()` runs on every `create()`, so a suite pays whatever is in it once per builder it makes, and one `password_hash()` at bcrypt cost 4 measures around 888 microseconds. That is 470 derived modifier calls for a single seeded password, which makes it the one thing worth moving out of a builder before anything the kernel does.

The rule ships two entries and guesses at no third. Both are PHP's own password API, both take a parameter whose whole job is to make them slower, and the message names it, so membership states a documented fact rather than an estimate. A general key derivation like `hash_pbkdf2()` is left alone on purpose, because it also derives encryption keys where a test may want the real thing.

Everything else expensive is your knowledge, so you declare it. An entry carries the reason it is there, because that reason ends up in the message somebody has to act on.

```neon
parameters:
    immutableTestBuilder:
        costlyInSeed:
            'App\Security\Hasher::derive': 'its iteration count'
            'my_slow_helper': 'the sleep it does'
```

A bare name is a function, a name with `::` is a static call. Instance calls are not matched, and that is deliberate rather than unfinished - a builder cannot declare a constructor because the kernel seals it, so it holds no injected service, and an instance call inside `seed()` is the builder talking to itself.

Declare only calls whose result is an immutable **value**. The message tells a reader to hoist or memoise, which is safe for a string and wrong for an object, because one instance shared by every builder means a test mutating it mutates it for all of them - the exact isolation the shallow clone above is careful to give you. A nested builder is the clearest thing not to declare. When a valid default needs a related entity, building one per `seed()` is what makes the default valid, not a cost to remove.

---

## Maintained by Rector

The bundled `RemoveRedundantModifierRector` deletes every modifier whose body only does what the kernel already derives - `with*(x)` assigning the bare parameter, `as*()` raising a bool, `without*()` writing the inferred empty value, each as the exact property-map `mutate()` one-liner - and leaves a `@method self` tag on the class in its place. The magic `__call` takes over with identical behaviour, and the tag keeps the IDE autocomplete the deleted method used to provide.

```php
use Pizgariu\ImmutableTestBuilder\Rector\RemoveRedundantModifierRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/tests'])
    ->withRules([RemoveRedundantModifierRector::class]);
```

A matching body shape alone never removes a method - the kernel itself must answer the same call. A hand-rolled builder with its own `mutate()` has no `__call` to catch the name once the method is gone, a `#[NotMagic]`-sealed ingredient makes the derivation refuse, and so does a flag the writers will not treat as one. Each keeps its body, pinned by a fixture that must come out unchanged.

`rector/rector` stays in `require-dev` here and in yours - the package ships zero runtime dependencies and the rule class loads only when rector runs. The 1.x series requires `phpstan/phpstan ^1.12.5`, the same floor as the rule set, so one toolchain carries both.

## How it compares

The PHP test-data space is mostly about persistence. This package deliberately is not.

| Package | What it does | Where this package differs |
|---|---|---|
| [`zenstruck/foundry`](https://packagist.org/packages/zenstruck/foundry) | Entity factories for Symfony and Doctrine - states, faker defaults, persisted object graphs | Foundry is at home in a booted Symfony app persisting Doctrine entities. This kernel builds any object in memory - value objects, DTOs, aggregates, plain arrays - with zero dependencies, and its DSL is enforced at analysis time instead of discovered at runtime |
| [`nelmio/alice`](https://packagist.org/packages/nelmio/alice) | Fixtures declared in YAML with faker expressions | YAML is invisible to static analysis and refactoring - a renamed property breaks the fixture at runtime. Every builder here is plain typed PHP that PHPStan and an IDE follow end to end |
| [`liip/test-fixtures-bundle`](https://packagist.org/packages/liip/test-fixtures-bundle) | Loads Doctrine fixtures into a test database and caches database states | A different layer entirely - it loads data, this package constructs objects. The two compose in one suite |
| Hand-written builders | The classic pattern, one bespoke builder set per project | The kernel deletes the boilerplate (trivial modifiers stop existing as code) and the bundled rules keep the discipline that bespoke builders lose over time |

Foundry and this package are two layers, not two answers - persisted entity graphs for integration tests there, valid domain objects for unit and application tests here.

---

## Install

```
composer require --dev pizgariu/immutable-test-builder
```

PHP 8.3+ (`^8.3`). Nothing else - the package has zero runtime dependencies.

---

## Versioning and roadmap

The project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html). The API surface described above is stable for 1.0.0.

- **2.0.0** - PHPStan 2.0 and Rector 2.x support land on this line.

A built-in data generator is not on any roadmap - randomness stays the project's choice, plugged in through `seed()`. Bulk collection utilities are out of scope as well - the magic `including*` and `excluding*` already cover single-item appends and removals, and anything richer deserves a handwritten modifier.

---

## Development

```
composer install
vendor/bin/phpunit
vendor/bin/phpstan analyse
vendor/bin/php-cs-fixer fix
```

The kernel is measured too, with [PHPBench](https://phpbench.readthedocs.io) under `benchmark`.

```
composer benchmark            # read the subjects against each other
composer benchmark:baseline   # store this machine's readings as the reference
composer benchmark:check      # re-run and fail on drift beyond the tolerance
```

Every claim there is a ratio, never a duration, because a duration means nothing on somebody else's hardware. `PrefixBenchmark` proves the grammar lookup costs the same whatever prefix it resolves, `MagicModifierBenchmark` prices a derived modifier against a handwritten one that ends in the same `mutate()`, and `KernelBenchmark` proves a chain stays linear in the number of modifiers. CI runs them once with a single revolution, which proves they still execute without asserting a stopwatch a shared runner cannot hold still.

PHPStan runs at level max over `src` and `tests`, with the bundled rule set included - the package dogfoods its own DSL. Coding style is enforced by php-cs-fixer on a PER-CS 2.0 base with the risky set on. CI validates `composer.json` strictly, then runs the suite and the analysis on PHP 8.3, 8.4 and 8.5, and checks the style once, on every push to `master` and every pull request. `fail-fast` is off, so a break on one interpreter does not hide the others.

---

## License

Released under the MIT License. See [LICENSE](LICENSE).
