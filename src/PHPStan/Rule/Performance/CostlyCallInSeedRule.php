<?php

declare(strict_types=1);

namespace Pizgariu\ImmutableTestBuilder\PHPStan\Rule\Performance;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use Pizgariu\ImmutableTestBuilder\PHPStan\Analyser\BuilderScope;
use Pizgariu\ImmutableTestBuilder\PHPStan\KernelMethod;

/**
 * seed() runs on every create(), so a suite pays whatever is in it once per
 * builder it makes. This says so about the calls where it is worth saying.
 *
 * The package ships two entries and will never guess at a third. password_hash()
 * and crypt() are PHP's own password API, where a work factor is the documented
 * purpose rather than an accident, so calling one per builder is expensive by
 * definition and not by our estimate.
 *
 * Everything else expensive is the project's own knowledge, so the project
 * declares it. A configured entry has to carry the reason it is there, exactly as
 * the shipped ones do, because a message that cannot say WHY a call is slow is an
 * opinion wearing a rule's clothes.
 *
 * Functions and static calls only. A builder cannot declare a constructor, since
 * the kernel seals it, so it holds no injected service and what stands in a seed()
 * is a function or a static factory. An instance call there is the builder talking
 * to itself, which is not what this is about.
 *
 * Advice about cost is not a term of the contract, which is why this lives apart
 * from the rules that police how a builder is written and ships in its own include
 * a project turns on deliberately.
 *
 * @implements Rule<InClassMethodNode>
 *
 * @internal
 */
final class CostlyCallInSeedRule implements Rule
{
    /**
     * PHP's own password API, mapped to the parameter that exists to slow it
     * down. Membership is that parameter, so each entry states a documented fact.
     *
     * A general key derivation like hash_pbkdf2() is deliberately absent, because
     * it also derives encryption keys, where a test may want the real thing. That
     * call is the project's to declare, not ours to assume.
     *
     * @var array<string, string>
     */
    private const array ALWAYS_COSTLY = [
        'password_hash' => 'its cost',
        'crypt' => 'the work factor in its salt',
    ];

    /**
     * @param array<string, string> $costlyInSeed a function or Class::method, each mapped to the reason it belongs here
     */
    public function __construct(private readonly array $costlyInSeed = []) {}

    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }

    /**
     * @throws ShouldNotHappenException
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $class = BuilderScope::kernel($scope);

        if (null === $class) {
            return [];
        }

        $method = $node->getOriginalNode();

        if (KernelMethod::Seed->value !== $method->name->toString() || null === $method->stmts) {
            return [];
        }

        $costly = self::ALWAYS_COSTLY;

        foreach ($this->costlyInSeed as $target => $reason) {
            $costly[strtolower($target)] = $reason;
        }

        $finder = new NodeFinder();

        /** @var list<FuncCall|StaticCall> $calls */
        $calls = [
            ...$finder->findInstanceOf($method->stmts, FuncCall::class),
            ...$finder->findInstanceOf($method->stmts, StaticCall::class),
        ];

        $errors = [];

        foreach ($calls as $call) {
            $called = self::nameOf($call, $scope);
            $reason = null === $called ? null : ($costly[strtolower($called)] ?? null);

            if (null === $called || null === $reason) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(sprintf(
                'seed() on builder %s calls %s(), which is slow on purpose - %s says so. seed() runs on every create(), so the suite pays that once per builder it makes. Hoist the result to a constant or memoise it on a project-owned abstract base, and if a test needs the real cost, spend it there rather than everywhere.',
                $class->getDisplayName(),
                $called,
                $reason,
            ))
                ->identifier('immutableTestBuilder.costlyCallInSeed')
                ->line($call->getStartLine())
                ->build()
            ;
        }

        return $errors;
    }

    /**
     * The name a configured entry would spell - a bare function, or the class a
     * static call resolves to joined to its method. self and static resolve
     * through the scope, so a project names the class it actually wrote.
     */
    private static function nameOf(FuncCall|StaticCall $call, Scope $scope): ?string
    {
        if ($call instanceof FuncCall) {
            return $call->name instanceof Name ? $call->name->toString() : null;
        }

        if (!$call->name instanceof Identifier) {
            return null;
        }

        if ($call->class instanceof Name) {
            $class = $scope->resolveName($call->class);
        } else {
            $class = $scope->getType($call->class)->getObjectClassNames()[0] ?? null;
        }

        return null === $class ? null : $class . '::' . $call->name->toString();
    }
}
