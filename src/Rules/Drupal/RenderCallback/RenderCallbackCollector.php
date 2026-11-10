<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal\RenderCallback;

use Drupal\Core\Render\Element\RenderCallbackInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use PhpParser\Node;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Type;
use function count;
use function str_contains;

/**
 * Collects render array callbacks whose target class implements
 * TrustedCallbackInterface.
 *
 * The render array can be in any class. RenderCallbackTrustedCallbacksRule
 * matches these callbacks against what TrustedCallbacksCollector records.
 *
 * A callback names its class when it is a string like 'Foo::bar' or an array
 * like [Foo::class, 'bar']. Drupal then calls Foo::trustedCallbacks(). A
 * callback like [$this, 'bar'] runs on an object, which may be a subclass.
 *
 * For a callback on the class the code is in, enclosingMethod is the method
 * holding the callback. A subclass overriding that method does not use it.
 *
 * @phpstan-type RenderCallback array{
 *     class: string,
 *     method: string,
 *     namesClass: bool,
 *     enclosingMethod: string|null,
 *     renderKey: string,
 *     position: int,
 *     file: string,
 *     line: int,
 * }
 * @implements Collector<ArrayItem, list<RenderCallback>>
 */
final class RenderCallbackCollector implements Collector
{

    private RenderCallbackResolver $renderCallbackResolver;

    public function __construct(RenderCallbackResolver $renderCallbackResolver)
    {
        $this->renderCallbackResolver = $renderCallbackResolver;
    }

    public function getNodeType(): string
    {
        return ArrayItem::class;
    }

    /**
     * @param Scope&DependencyTracker $scope
     */
    public function processNode(Node $node, Scope $scope): ?array
    {
        $renderKey = $this->renderCallbackResolver->findRenderKey($node, $scope);
        if ($renderKey === null) {
            return null;
        }
        $callbackExpressions = $this->renderCallbackResolver->findCallbackExpressions($renderKey, $node->value);
        if ($callbackExpressions === null) {
            return null;
        }

        $callbacks = [];
        foreach ($callbackExpressions as $position => $callbackExpression) {
            foreach ($this->findCallbacks($callbackExpression, $scope) as [$class, $method, $namesClass]) {
                $callbacks[] = [
                    'class' => $class,
                    'method' => $method,
                    'namesClass' => $namesClass,
                    'enclosingMethod' => $this->findEnclosingMethod($class, $namesClass, $scope),
                    'renderKey' => $renderKey,
                    'position' => $position,
                    'file' => $scope->getFile(),
                    'line' => $callbackExpression->getStartLine(),
                ];
            }
        }
        return count($callbacks) === 0 ? null : $callbacks;
    }

    /**
     * @param Scope&DependencyTracker $scope
     * @return list<array{string, string, bool}>
     */
    private function findCallbacks(Expr $callbackExpression, Scope $scope): array
    {
        $callbacks = [];
        foreach ($this->renderCallbackResolver->resolveType($callbackExpression, $scope)->getConstantArrays() as $callbackArray) {
            if (!$callbackArray->isCallable()->yes()) {
                continue;
            }
            $namesClass = $this->namesClass($scope->getType($callbackExpression), $callbackArray);
            foreach ($callbackArray->findTypeAndMethodNames() as $typeAndMethodName) {
                foreach ($typeAndMethodName->getType()->getObjectClassReflections() as $classReflection) {
                    if (!$classReflection->implementsInterface(TrustedCallbackInterface::class)
                        || $classReflection->implementsInterface(RenderCallbackInterface::class)
                    ) {
                        continue;
                    }
                    $callbacks[] = [$classReflection->getName(), $typeAndMethodName->getMethod(), $namesClass];
                }
            }
        }
        return $callbacks;
    }

    private function findEnclosingMethod(string $callbackClass, bool $namesClass, Scope $scope): ?string
    {
        if ($namesClass
            || !$scope->isInClass()
            || $scope->getClassReflection()->getName() !== $callbackClass
        ) {
            return null;
        }
        return $scope->getFunctionName();
    }

    /**
     * Whether Drupal calls trustedCallbacks() on the class the callback names.
     */
    private function namesClass(Type $callbackType, ConstantArrayType $callbackArray): bool
    {
        $callbackStrings = $callbackType->getConstantStrings();
        if (count($callbackStrings) > 0) {
            foreach ($callbackStrings as $callbackString) {
                if (!str_contains($callbackString->getValue(), '::')) {
                    return false;
                }
            }
            return true;
        }
        $classType = $callbackArray->getValueTypes()[0] ?? null;
        return $classType !== null && count($classType->getConstantStrings()) > 0;
    }
}
