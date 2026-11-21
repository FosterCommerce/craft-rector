<?php

declare(strict_types=1);

namespace craft\rector\Rector;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Rector\Renaming\ValueObject\RenameStaticMethod;
use Rector\RuleDoc\CodeSample\ConfiguredCodeSample;
use Rector\RuleDoc\RuleDefinition;

/**
 * Replaces RenameStaticMethodRector, which Rector 2.8 turned into a no-op.
 */
final class RenameStaticMethodRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var RenameStaticMethod[]
     */
    private array $staticMethodRenames = [];

    /**
     * @param RenameStaticMethod[] $configuration
     */
    public function configure(array $configuration): void
    {
        $this->staticMethodRenames = $configuration;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Renames a static method, and its class when that changed', [
            new ConfiguredCodeSample(
                'Db::extractGlue($param);',
                'QueryParam::extractOperator($param);',
                [new RenameStaticMethod('craft\helpers\Db', 'extractGlue', 'craft\db\QueryParam', 'extractOperator')],
            ),
        ]);
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [StaticCall::class];
    }

    /**
     * @param StaticCall $node
     */
    public function refactor(Node $node): ?Node
    {
        foreach ($this->staticMethodRenames as $staticMethodRename) {
            if (! $this->isName($node->name, $staticMethodRename->getOldMethod())) {
                continue;
            }

            if (! $this->isObjectType($node->class, $staticMethodRename->getOldObjectType())) {
                continue;
            }

            $node->name = new Identifier($staticMethodRename->getNewMethod());

            if ($staticMethodRename->hasClassChanged()) {
                $node->class = new FullyQualified($staticMethodRename->getNewClass());
            }

            return $node;
        }

        return null;
    }
}
