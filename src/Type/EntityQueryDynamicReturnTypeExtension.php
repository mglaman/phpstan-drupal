<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type;

use Drupal\Core\Entity\Query\QueryInterface;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ArrayType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;

class EntityQueryDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

    public function getClass(): string
    {
        return QueryInterface::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'execute';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type {
        $defaultReturnType = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

        $queryBuilderType = new ObjectType(QueryInterface::class);
        $var = $methodCall->var;
        while ($var instanceof MethodCall) {
            $varType = $scope->getType($var->var);
            if (!$queryBuilderType->isSuperTypeOf($varType)->yes()) {
                return new ArrayType(new IntegerType(), new StringType());
            }

            $nameObject = $var->name;
            if (!($nameObject instanceof Identifier)) {
                return $defaultReturnType;
            }

            $name = $nameObject->toString();
            if ($name === 'count') {
                return new IntegerType();
            }

            $var = $var->var;
        }

        return $defaultReturnType;
    }
}
