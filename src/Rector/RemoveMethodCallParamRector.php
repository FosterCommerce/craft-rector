<?php

declare(strict_types=1);

namespace craft\rector\Rector;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\NodeAnalyzer\ArgsAnalyzer;
use Rector\Rector\AbstractRector;
use Rector\Removing\ValueObject\ArgumentRemover;
use Rector\RuleDoc\CodeSample\ConfiguredCodeSample;
use Rector\RuleDoc\RuleDefinition;

/**
 * Replaces RemoveMethodCallParamRector, which Rector 2.8 turned into a no-op.
 */
final class RemoveMethodCallParamRector extends AbstractRector implements ConfigurableRectorInterface
{
    private const REMOVED_ATTRIBUTE = 'craftRemovedArguments';

    /**
     * @var ArgumentRemover[]
     */
    private array $argumentRemovers = [];

    public function __construct(
        private readonly ArgsAnalyzer $argsAnalyzer,
    ) {
    }

    /**
     * @param ArgumentRemover[] $configuration
     */
    public function configure(array $configuration): void
    {
        $this->argumentRemovers = $configuration;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Removes an argument from a method call', [
            new ConfiguredCodeSample(
                'Db::insert($table, $columns, false, $db);',
                'Db::insert($table, $columns, $db);',
                [new ArgumentRemover('craft\helpers\Db', 'insert', 2, null)],
            ),
        ]);
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [MethodCall::class, StaticCall::class];
    }

    /**
     * @param MethodCall|StaticCall $node
     */
    public function refactor(Node $node): ?Node
    {
        // Leave named arguments alone, since their order doesn't match the parameter positions
        if ($node->isFirstClassCallable() || $this->argsAnalyzer->hasNamedArg($node->getArgs())) {
            return null;
        }

        $caller = $node instanceof StaticCall ? $node->class : $node->var;
        /** @var array<int, true> $removedEntries */
        $removedEntries = $node->getAttribute(self::REMOVED_ATTRIBUTE, []);
        $hasChanged = false;

        foreach ($this->argumentRemovers as $entryIndex => $argumentRemover) {
            // Skip an entry already applied, since the next traversal would remove the following argument
            if (isset($removedEntries[$entryIndex]) || ! isset($node->args[$argumentRemover->getPosition()])) {
                continue;
            }

            if (! $this->isName($node->name, $argumentRemover->getMethod()) || ! $this->isObjectType($caller, $argumentRemover->getObjectType())) {
                continue;
            }

            unset($node->args[$argumentRemover->getPosition()]);
            $removedEntries[$entryIndex] = true;
            $hasChanged = true;
        }

        if (! $hasChanged) {
            return null;
        }

        $node->setAttribute(self::REMOVED_ATTRIBUTE, $removedEntries);

        return $node;
    }
}
