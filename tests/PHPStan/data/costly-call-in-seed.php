<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Data\CostlyCallInSeed;

use Pizgariu\ImmutableTestBuilder\Implementation\AbstractBuilder;

/**
 * Pays a work factor once per create(), which is what the rule is for. The
 * pbkdf2 call beside it is NOT reported, because a general key derivation also
 * derives encryption keys and the rule refuses to guess which one this is.
 *
 * @extends AbstractBuilder<string>
 */
final class HashingSeedBuilder extends AbstractBuilder
{
    private string $hash = '';

    private string $derived = '';

    public function build(): string
    {
        return $this->hash;
    }

    protected function seed(): void
    {
        $this->hash = password_hash('TestPassword123!', PASSWORD_BCRYPT, ['cost' => 4]);
        $this->derived = hash_pbkdf2('sha256', 'TestPassword123!', 'salt', 1000);
    }
}

/**
 * Pays it inside a closure, which is the same cost wearing a hat.
 *
 * @extends AbstractBuilder<string>
 */
final class LazyLookingSeedBuilder extends AbstractBuilder
{
    private string $hash = '';

    public function build(): string
    {
        return $this->hash;
    }

    protected function seed(): void
    {
        $make = static fn (): string => password_hash('TestPassword123!', PASSWORD_BCRYPT, ['cost' => 4]);

        $this->hash = $make();
    }
}

/**
 * A static factory the PROJECT declares as costly through configuration, beside
 * one it does not, so the config is proved to be what decides and not the shape.
 *
 * @extends AbstractBuilder<string>
 */
final class StaticFactorySeedBuilder extends AbstractBuilder
{
    private string $token = '';

    private string $cheap = '';

    public function build(): string
    {
        return $this->token;
    }

    protected function seed(): void
    {
        $this->token = SlowVault::derive('secret');
        $this->cheap = SlowVault::label();
    }
}

final class SlowVault
{
    public static function derive(string $secret): string
    {
        return $secret;
    }

    public static function label(): string
    {
        return 'vault';
    }
}

/**
 * Clean twice over. The hash is a precomputed constant, and the one modifier that
 * does spend a work factor spends it per call rather than per builder, which is
 * the whole point of moving it out of seed().
 *
 * @extends AbstractBuilder<string>
 */
final class PrecomputedSeedBuilder extends AbstractBuilder
{
    private const string BCRYPT_OF_TEST_PASSWORD = '$2y$04$abcdefghijklmnopqrstuv';

    private string $hash = '';

    public function build(): string
    {
        return $this->hash;
    }

    public function withPassword(string $password): static
    {
        return $this->mutate(['hash' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 4])]);
    }

    protected function seed(): void
    {
        $this->hash = self::BCRYPT_OF_TEST_PASSWORD;
    }
}
