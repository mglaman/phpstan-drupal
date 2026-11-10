<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type;

use Drupal\Core\Entity\EntityTypeInterface;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;

/**
 * @author Daniel Phin <pro@danielph.in>
 */
class EntityTypeLinkTemplateByExistence implements DynamicMethodReturnTypeExtension
{

    public function getClass(): string
    {
        return EntityTypeInterface::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'getLinkTemplate';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): ?Type {
        if (null === ($keyArg = $methodCall->getArg('key', 0))) {
            return null;
        }

        $originalReturnType = $methodReflection->getVariants()[0]->getReturnType();
        $hasKeyMethodCall = new MethodCall($methodCall->var, new Identifier('hasLinkTemplate'), [$keyArg]);
        if ($scope->getType($hasKeyMethodCall)->isTrue()->yes()) {
            return $originalReturnType->tryRemove(new ConstantBooleanType(false));
        } elseif ($scope->getType($hasKeyMethodCall)->isFalse()->yes()) {
            return $originalReturnType->tryRemove(new StringType());
        }

        return $originalReturnType;
    }
}
