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
