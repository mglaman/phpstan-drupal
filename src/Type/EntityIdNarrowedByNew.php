<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type;

use Drupal\Core\Entity\EntityInterface;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;

/**
 * @author Daniel Phin <pro@danielph.in>
 */
class EntityIdNarrowedByNew implements DynamicMethodReturnTypeExtension
{

    public function getClass(): string
    {
        return EntityInterface::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'id';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): ?Type {
        // Usually contains a UnionType with IntegerType, StringType, NullType,
        // unless it was narrowed by a child.
        $originalReturnType = $methodReflection->getVariants()[0]->getReturnType();
        $isNewMethodCall = new MethodCall($methodCall->var, new Identifier('isNew'));
        if ($scope->getType($isNewMethodCall)->isFalse()->yes()) {
            // Remove NULL.
            return $originalReturnType->tryRemove(new NullType());
        }

        return $originalReturnType;
    }
}
