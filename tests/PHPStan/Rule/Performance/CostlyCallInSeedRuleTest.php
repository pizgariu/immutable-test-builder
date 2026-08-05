<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Rule\Performance;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Pizgariu\ImmutableTestBuilder\PHPStan\Rule\Performance\CostlyCallInSeedRule;

/**
 * @extends RuleTestCase<CostlyCallInSeedRule>
 */
final class CostlyCallInSeedRuleTest extends RuleTestCase
{
    /** @var array<string, string> */
    private array $configured = [];

    protected function getRule(): Rule
    {
        return new CostlyCallInSeedRule($this->configured);
    }

    public function testReportsThePasswordApiItShipsWithAndSparesEverythingElse(): void
    {
        $builder = 'Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Data\CostlyCallInSeed';

        $this->analyse([__DIR__ . '/../../data/costly-call-in-seed.php'], [
            [
                sprintf('seed() on builder %s\HashingSeedBuilder calls password_hash(), which is slow on purpose - its cost says so. seed() runs on every create(), so the suite pays that once per builder it makes. Hoist the result to a constant or memoise it on a project-owned abstract base, and if a test needs the real cost, spend it there rather than everywhere.', $builder),
                29,
            ],
            [
                sprintf('seed() on builder %s\LazyLookingSeedBuilder calls password_hash(), which is slow on purpose - its cost says so. seed() runs on every create(), so the suite pays that once per builder it makes. Hoist the result to a constant or memoise it on a project-owned abstract base, and if a test needs the real cost, spend it there rather than everywhere.', $builder),
                50,
            ],
        ]);
    }

    /**
     * The same file, a different configuration, a different answer. What the
     * project declares is what decides, and a static call beside the declared one
     * stays silent to prove the shape is not what matched.
     */
    public function testReportsWhatTheProjectDeclaresOnTopOfIt(): void
    {
        $builder = 'Pizgariu\ImmutableTestBuilder\Tests\PHPStan\Data\CostlyCallInSeed';

        $this->configured = [$builder . '\SlowVault::derive' => 'the key stretching it does'];

        $this->analyse([__DIR__ . '/../../data/costly-call-in-seed.php'], [
            [
                sprintf('seed() on builder %s\HashingSeedBuilder calls password_hash(), which is slow on purpose - its cost says so. seed() runs on every create(), so the suite pays that once per builder it makes. Hoist the result to a constant or memoise it on a project-owned abstract base, and if a test needs the real cost, spend it there rather than everywhere.', $builder),
                29,
            ],
            [
                sprintf('seed() on builder %s\LazyLookingSeedBuilder calls password_hash(), which is slow on purpose - its cost says so. seed() runs on every create(), so the suite pays that once per builder it makes. Hoist the result to a constant or memoise it on a project-owned abstract base, and if a test needs the real cost, spend it there rather than everywhere.', $builder),
                50,
            ],
            [
                sprintf('seed() on builder %1$s\StaticFactorySeedBuilder calls %1$s\SlowVault::derive(), which is slow on purpose - the key stretching it does says so. seed() runs on every create(), so the suite pays that once per builder it makes. Hoist the result to a constant or memoise it on a project-owned abstract base, and if a test needs the real cost, spend it there rather than everywhere.', $builder),
                75,
            ],
        ]);
    }
}
