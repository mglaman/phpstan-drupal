<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type\EntityQuery;

use Drupal\Core\Entity\Query\QueryInterface;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\MethodTypeSpecifyingExtension;

final class AccessCheckTypeSpecifyingExtension implements MethodTypeSpecifyingExtension, TypeSpecifierAwareExtension
{
    private TypeSpecifier $typeSpecifier;
    public function setTypeSpecifier(TypeSpecifier $typeSpecifier) : void
    {
        $this->typeSpecifier = $typeSpecifier;
    }

    public function getClass(): string
    {
        return QueryInterface::class;
    }

    public function isMethodSupported(
        MethodReflection $methodReflection,
        MethodCall $node,
        TypeSpecifierContext $context
    ): bool {
        return $methodReflection->getName() === 'accessCheck';
    }

    public function specifyTypes(
        MethodReflection $methodReflection,
        MethodCall $node,
        Scope $scope,
        TypeSpecifierContext $context
    ): SpecifiedTypes {
        $returnType = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        $expr = $node->var;
        if (!$returnType instanceof EntityQueryType) {
            return new SpecifiedTypes([]);
        }
        return $this->typeSpecifier->create(
            $expr,
            $returnType->withAccessCheck(),
            TypeSpecifierContext::createTruthy(),
            true
        );
    }
}
