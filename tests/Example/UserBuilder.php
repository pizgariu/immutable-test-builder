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
