<?php

declare(strict_types=1);

namespace craft\rector\Rector;

use PhpParser\Node;
use PhpParser\Node\Stmt\Property;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\StringType;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Exception\ShouldNotHappenException;
use Rector\PHPStan\ScopeFetcher;
use Rector\PHPStanStaticTypeMapper\Enum\TypeKind;
use Rector\Rector\AbstractRector;
use Rector\RuleDoc\CodeSample\ConfiguredCodeSample;
use Rector\RuleDoc\RuleDefinition;
use Rector\StaticTypeMapper\StaticTypeMapper;
use Rector\TypeDeclaration\ValueObject\AddPropertyTypeDeclaration;

/**
 * Replaces AddPropertyTypeDeclarationRector, which Rector 2.8 turned into a no-op.
 */
final class AddPropertyTypeDeclarationRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var AddPropertyTypeDeclaration[]
     */
    private array $addPropertyTypeDeclarations = [];

    public function __construct(
        private readonly StaticTypeMapper $staticTypeMapper,
    ) {
    }

    /**
     * @param AddPropertyTypeDeclaration[] $configuration
     */
    public function configure(array $configuration): void
    {
        $this->addPropertyTypeDeclarations = $configuration;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Adds a type to an untyped property a parent class types', [
            new ConfiguredCodeSample(
                'public $name;',
                'public string $name;',
                [new AddPropertyTypeDeclaration('ParentClass', 'name', new StringType())],
            ),
        ]);
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Property::class];
    }

    /**
     * @param Property $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node->type !== null) {
            return null;
        }

        $classReflection = ScopeFetcher::fetch($node)->getClassReflection();

        if (! $classReflection instanceof ClassReflection) {
            return null;
        }

        foreach ($this->addPropertyTypeDeclarations as $addPropertyTypeDeclaration) {
            $className = $addPropertyTypeDeclaration->getClass();

            if (! $classReflection->hasTraitUse($className) && ! $classReflection->is($className)) {
                continue;
            }

            if (! $this->isName($node, $addPropertyTypeDeclaration->getPropertyName())) {
                continue;
            }

            $typeNode = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($addPropertyTypeDeclaration->getType(), TypeKind::PROPERTY);

            if (! $typeNode instanceof Node) {
                throw new ShouldNotHappenException('Invalid property type configuration.');
            }

            $node->type = $typeNode;

            return $node;
        }

        return null;
    }
}
