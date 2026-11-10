<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal\RenderCallback;

use Drupal\Core\Security\TrustedCallbackInterface;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Type\Type;
use function count;
use function strtolower;

/**
 * Collects the method names each trustedCallbacks() method returns.
 *
 * The method names are NULL when a return statement is not an array of
 * constant strings, such as array_merge(parent::trustedCallbacks(), [...]).
 *
 * @implements Collector<MethodReturnStatementsNode, array{string, list<string>|null}>
 */
final class TrustedCallbacksCollector implements Collector
{

    public function getNodeType(): string
    {
        return MethodReturnStatementsNode::class;
    }

    public function processNode(Node $node, Scope $scope): ?array
    {
        if (strtolower($node->getMethodName()) !== 'trustedcallbacks') {
            return null;
        }
        $classReflection = $node->getClassReflection();
        if (!$classReflection->implementsInterface(TrustedCallbackInterface::class)) {
            return null;
        }
        return [$classReflection->getName(), $this->findReturnedMethodNames($node)];
    }

    /**
     * @return list<string>|null
     */
    private function findReturnedMethodNames(MethodReturnStatementsNode $node): ?array
    {
        if ($node->isGenerator() || count($node->getReturnStatements()) === 0) {
            return null;
        }
        $methodNames = [];
        foreach ($node->getReturnStatements() as $returnStatement) {
            $returnedExpr = $returnStatement->getReturnNode()->expr;
            if ($returnedExpr === null) {
                return null;
            }
            $returnedMethodNames = $this->findConstantStringValues($returnStatement->getScope()->getType($returnedExpr));
            if ($returnedMethodNames === null) {
                return null;
            }
            foreach ($returnedMethodNames as $methodName) {
                $methodNames[] = $methodName;
            }
        }
        return $methodNames;
    }

    /**
     * @return list<string>|null
     */
    private function findConstantStringValues(Type $type): ?array
    {
        if (!$type->isConstantArray()->yes()) {
            return null;
        }
        $values = [];
        foreach ($type->getConstantArrays() as $constantArray) {
            foreach ($constantArray->getValueTypes() as $valueType) {
                $constantStrings = $valueType->getConstantStrings();
                if (count($constantStrings) === 0) {
                    return null;
                }
                foreach ($constantStrings as $constantString) {
                    $values[] = $constantString->getValue();
                }
            }
        }
        return $values;
    }
}
