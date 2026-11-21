<?php

declare(strict_types=1);

namespace craft\rector\Rector;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Type;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\PHPStan\ScopeFetcher;
use Rector\Rector\AbstractRector;
use Rector\Renaming\ValueObject\MethodCallRename;
use Rector\RuleDoc\CodeSample\ConfiguredCodeSample;
use Rector\RuleDoc\RuleDefinition;

/**
 * Renames calls Rector 2.8's RenameMethodRector skips: from a subclass that declares the old method
 * and inherits the new one, and along a chain of renamed calls.
 */
final class RenameMethodCallRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var MethodCallRename[]
     */
    private array $methodCallRenames = [];

    /**
     * @param MethodCallRename[] $configuration
     */
    public function configure(array $configuration): void
    {
        $this->methodCallRenames = $configuration;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Renames calls to a renamed method', [
            new ConfiguredCodeSample(
                '$this->getThumbUrl(212);',
                '$this->thumbUrl(212);',
                [new MethodCallRename('craft\base\Element', 'getThumbUrl', 'thumbUrl')],
            ),
        ]);
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [MethodCall::class, NullsafeMethodCall::class, StaticCall::class];
    }

    /**
     * @param MethodCall|NullsafeMethodCall|StaticCall $node
     */
    public function refactor(Node $node): ?Node
    {
        $callerType = $node instanceof StaticCall ? $this->getType($node->class) : $this->resolveCallerType($node->var);

        foreach ($this->methodCallRenames as $methodCallRename) {
            if (! $this->isName($node->name, $methodCallRename->getOldMethod())) {
                continue;
            }

            if (! $methodCallRename->getObjectType()->isSuperTypeOf($callerType)->yes() || $this->overridesNewMethod($callerType, $methodCallRename)) {
                continue;
            }

            $node->name = new Identifier($methodCallRename->getNewMethod());

            return $node;
        }

        return null;
    }

    /**
     * Skip a class that declares the old method and its own new one, since the calls keep reaching its own method.
     */
    private function overridesNewMethod(Type $callerType, MethodCallRename $methodCallRename): bool
    {
        foreach ($callerType->getObjectClassReflections() as $classReflection) {
            if ($classReflection->getName() === $methodCallRename->getClass()
                || ! $classReflection->hasNativeMethod($methodCallRename->getOldMethod())
                || ! $classReflection->hasNativeMethod($methodCallRename->getNewMethod())) {
                continue;
            }

            if ($classReflection->getNativeMethod($methodCallRename->getNewMethod())->getDeclaringClass()->getName() !== $methodCallRename->getClass()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Type an inner call of a chain by the method it is renamed to, since the outer call is visited first
     * and the old method no longer exists to give it a type.
     */
    private function resolveCallerType(Expr $caller): Type
    {
        $type = $this->getType($caller);

        if (! $caller instanceof MethodCall || $type->isObject()->yes()) {
            return $type;
        }

        $methodName = $this->getName($caller->name);

        if ($methodName === null) {
            return $type;
        }

        $innerType = $this->resolveCallerType($caller->var);

        foreach ($this->methodCallRenames as $methodCallRename) {
            if (strcasecmp($methodCallRename->getOldMethod(), $methodName) === 0 && $methodCallRename->getObjectType()->isSuperTypeOf($innerType)->yes()) {
                $methodName = $methodCallRename->getNewMethod();
                break;
            }
        }

        if (! $innerType->hasMethod($methodName)->yes()) {
            return $type;
        }

        return ParametersAcceptorSelector::combineAcceptors($innerType->getMethod($methodName, ScopeFetcher::fetch($caller))->getVariants())->getReturnType();
    }
}
