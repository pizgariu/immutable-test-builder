<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\Example;

use PHPUnit\Framework\TestCase;

final class MembershipBuilderTest extends TestCase
{
    public function testCreateReturnsBuilderThatBuildsImmediately(): void
    {
        $membership = MembershipBuilder::create()->build();

        self::assertStringContainsString('@', $membership->email);
        self::assertSame('Ellen', $membership->firstName);
        self::assertSame(['member'], $membership->tags);
        self::assertFalse($membership->active);
    }

    public function testMagicPrefixesDeriveFromTheProperties(): void
    {
        $membership = MembershipBuilder::create()
            ->withEmail('ripley@example.test')
            ->asActive()
            ->includingTag('admin')
            ->excludingTag('member')
            ->build()
        ;

        self::assertSame('ripley@example.test', $membership->email);
        self::assertTrue($membership->active);
        self::assertSame(['admin'], $membership->tags);
        self::assertSame([], MembershipBuilder::create()->withoutTags()->build()->tags);
    }

    public function testFromHydratesSeveralFieldsFromASource(): void
    {
        $membership = MembershipBuilder::create()
            ->fromApplicant(new Applicant('sarah@example.test', 'Sarah', 'Connor'))
            ->build()
        ;

        self::assertSame('sarah@example.test', $membership->email);
        self::assertSame('Sarah', $membership->firstName);
        self::assertSame('Connor', $membership->lastName);
    }

    public function testForEstablishesOwnership(): void
    {
        $membership = MembershipBuilder::create()->forAccount(new Account(42))->build();

        self::assertSame(42, $membership->accountId);
    }

    public function testHavingMutatesAMultiPropertyConceptAtOnce(): void
    {
        $membership = MembershipBuilder::create()->havingName('Dutch', 'Schaefer')->build();

        self::assertSame('Dutch', $membership->firstName);
        self::assertSame('Schaefer', $membership->lastName);
    }

    public function testFromLeavesTheTrunkUntouched(): void
    {
        $base = MembershipBuilder::create();
        $before = $base->build();

        $base->fromApplicant(new Applicant('sarah@example.test', 'Sarah', 'Connor'))->build();

        self::assertSame($before->email, $base->build()->email);
    }
}
