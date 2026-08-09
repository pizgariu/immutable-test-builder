<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\Example;

use Pizgariu\ImmutableTestBuilder\Contract\Attribute\CoversBuiltType;
use Pizgariu\ImmutableTestBuilder\Implementation\AbstractBuilder;

/**
 * The DSL end to end. The magic __call derives with*, without*, as*,
 * including* and excluding* from the property declarations, so the only
 * modifiers written by hand are the three prefixes that are never magic -
 * from* hydrates from a source object, for* establishes ownership, and
 * having* mutates one inseparable multi-property concept atomically.
 *
 * It also promises coverage, so BuiltTypeCoverageRule holds this builder to
 * owning every ingredient Membership requires. The package's own analysis is
 * where that promise is checked, which keeps the rule from being a thing only
 * its unit test ever runs.
 *
 * @method MembershipBuilder withEmail(string $email)
 * @method MembershipBuilder withoutTags()
 * @method MembershipBuilder asActive(bool $active = true)
 * @method MembershipBuilder includingTag(string $tag)
 * @method MembershipBuilder excludingTag(string $tag)
 *
 * @extends AbstractBuilder<Membership>
 */
#[CoversBuiltType]
final class MembershipBuilder extends AbstractBuilder
{
    private string $email;

    private string $firstName;

    private string $lastName;

    private int $accountId;

    private bool $active;

    /** @var list<string> */
    private array $tags;

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

    public function build(): Membership
    {
        return new Membership(
            $this->email,
            $this->firstName,
            $this->lastName,
            $this->accountId,
            $this->active,
            $this->tags,
        );
    }

    protected function seed(): void
    {
        $suffix = random_int(1, 9999);

        $this->email = sprintf('member-%04d@example.test', $suffix);
        $this->firstName = 'Ellen';
        $this->lastName = 'Ripley';
        $this->accountId = $suffix;
        $this->active = false;
        $this->tags = ['member'];
    }
}
