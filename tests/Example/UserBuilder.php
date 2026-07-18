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
