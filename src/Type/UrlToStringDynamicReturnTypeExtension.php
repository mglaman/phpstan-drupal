<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type;

use Drupal\Core\GeneratedUrl;
use Drupal\Core\Url;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;

final class UrlToStringDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

    public function getClass(): string
    {
        return Url::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'toString';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type {
        if (0 === \count($methodCall->getArgs())) {
            return new StringType();
        }

        $arg1 = $scope->getType($methodCall->getArgs()[0]->value);
        if (!$arg1 instanceof ConstantBooleanType) {
            return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        }

        if (true === $arg1->getValue()) {
            return new ObjectType(GeneratedUrl::class);
        }

        return new StringType();
    }
}
