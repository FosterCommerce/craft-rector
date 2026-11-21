<?php

declare(strict_types=1);

namespace craft\rector\Rector;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PHPStan\Type\ObjectType;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Rector\RuleDoc\CodeSample\ConfiguredCodeSample;
use Rector\RuleDoc\RuleDefinition;

/**
 * Replaces method calls with property fetches.
 */
final class MethodCallToPropertyFetchRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var array<array{class: string, method: string, property: string}>
     */
    private array $methodCallsToPropertyFetches = [];

    /**
     * @param array<array{class: string, method: string, property: string}> $methodCallsToPropertyFetches
     */
    public function configure(array $methodCallsToPropertyFetches): void
    {
        $this->methodCallsToPropertyFetches = $methodCallsToPropertyFetches;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replaces method calls with property fetches', [
            new ConfiguredCodeSample(
                '$user->getFullName()',
                '$user->fullName',
                [[
                    'class' => 'craft\elements\User',
                    'method' => 'getFullName',
                    'property' => 'fullName',
                ]],
            ),
        ]);
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }

    /**
     * @param MethodCall $node
     */
    public function refactor(Node $node): ?Node
    {
        foreach ($this->methodCallsToPropertyFetches as $config) {
            if (! $this->isObjectType($node->var, new ObjectType($config['class']))) {
                continue;
            }

            if (! $this->isName($node->name, $config['method'])) {
                continue;
            }

            return new PropertyFetch($node->var, new Identifier($config['property']));
        }

        return null;
    }
}
