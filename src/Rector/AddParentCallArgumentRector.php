<?php

declare(strict_types=1);

namespace craft\rector\Rector;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\BooleanType;
use Rector\Arguments\ValueObject\ArgumentAdder;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\NodeAnalyzer\ArgsAnalyzer;
use Rector\PHPStan\ScopeFetcher;
use Rector\Rector\AbstractRector;
use Rector\Reflection\ReflectionResolver;
use Rector\RuleDoc\CodeSample\ConfiguredCodeSample;
use Rector\RuleDoc\RuleDefinition;

/**
 * Passes a required parameter added to an overriding method through to its parent call,
 * since Rector 2.8's ArgumentAdderRector skips a parent method that declares it without a default.
 */
final class AddParentCallArgumentRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var ArgumentAdder[]
     */
    private array $argumentAdders = [];

    public function __construct(
        private readonly ReflectionResolver $reflectionResolver,
        private readonly ArgsAnalyzer $argsAnalyzer,
    ) {
    }

    /**
     * @param ArgumentAdder[] $configuration
     */
    public function configure(array $configuration): void
    {
        $this->argumentAdders = $configuration;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Passes a required parameter added to an overriding method through to its parent call', [
            new ConfiguredCodeSample(
                'return parent::inputHtml($value, $element);',
                'return parent::inputHtml($value, $element, $inline);',
                [new ArgumentAdder('craft\base\Field', 'inputHtml', 2, 'inline', false, new BooleanType())],
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
        if (! $this->isName($node->class, 'parent') || $node->isFirstClassCallable()) {
            return null;
        }

        // Leave named arguments alone, since a positional argument can't follow them
        if ($this->argsAnalyzer->hasNamedArg($node->getArgs())) {
            return null;
        }

        $scope = ScopeFetcher::fetch($node);
        $classReflection = $scope->getClassReflection();

        if (! $classReflection instanceof ClassReflection) {
            return null;
        }

        foreach ($this->argumentAdders as $argumentAdder) {
            $argumentName = $argumentAdder->getArgumentName();

            if ($argumentName === null || ! $this->isName($node->name, $argumentAdder->getMethod())) {
                continue;
            }

            if (count($node->getArgs()) !== $argumentAdder->getPosition() || ! $classReflection->is($argumentAdder->getObjectType()->getClassName())) {
                continue;
            }

            if (! $this->isInOverridingMethod($scope, $argumentAdder->getMethod(), $argumentName)) {
                continue;
            }

            if (! $this->isParentParameterRequired($node, $argumentAdder->getPosition())) {
                continue;
            }

            $node->args[] = new Arg(new Variable($argumentName));

            return $node;
        }

        return null;
    }

    /**
     * Pass the argument only from the method that overrides the parent, where the parameter is defined.
     */
    private function isInOverridingMethod(Scope $scope, string $methodName, string $argumentName): bool
    {
        return strcasecmp((string) $scope->getFunctionName(), $methodName) === 0
            && $scope->hasVariableType($argumentName)->yes();
    }

    private function isParentParameterRequired(StaticCall $staticCall, int $position): bool
    {
        $methodReflection = $this->reflectionResolver->resolveMethodReflectionFromStaticCall($staticCall);

        if (! $methodReflection instanceof MethodReflection) {
            return false;
        }

        $parameter = ParametersAcceptorSelector::combineAcceptors($methodReflection->getVariants())->getParameters()[$position] ?? null;

        return $parameter !== null && ! $parameter->isOptional();
    }
}
