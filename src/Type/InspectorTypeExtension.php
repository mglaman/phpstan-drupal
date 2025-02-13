<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type;

use Drupal\Component\Assertion\Inspector;
use InvalidArgumentException;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\IterableType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StaticMethodTypeSpecifyingExtension;
use PHPStan\Type\StringType;
use function in_array;

final class InspectorTypeExtension implements StaticMethodTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

    private TypeSpecifier $typeSpecifier;

    public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
    {
        $this->typeSpecifier = $typeSpecifier;
    }

    public function getClass(): string
    {
        return Inspector::class;
    }

    public function isStaticMethodSupported(MethodReflection $staticMethodReflection, StaticCall $node, TypeSpecifierContext $context): bool
    {
        $implemented_methods = [
          'assertAllStrings',
          'assertAllArrays',
        ];

        return in_array($staticMethodReflection->getName(), $implemented_methods);
    }

    public function specifyTypes(MethodReflection $staticMethodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        return match ($staticMethodReflection->getName()) {
            default => throw new InvalidArgumentException(sprintf('Method %s is not yet implemented.', $staticMethodReflection->getName())),
            'assertAllStrings' => $this->specifyAssertAllStrings($staticMethodReflection, $node, $scope, $context),
            'assertAllArrays' => $this->specifyAssertAllArrays($staticMethodReflection, $node, $scope, $context),
        };
    }

    private function specifyAssertAllStrings(MethodReflection $staticMethodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        if (!isset($node->getArgs()[0])) {
            return new SpecifiedTypes();
        }

        $newType = new IterableType(new MixedType(true), new StringType());

        return $this->typeSpecifier->create($node->getArgs()[0]->value, $newType, $context, $scope);
    }

    private function specifyAssertAllArrays(MethodReflection $staticMethodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        if (!isset($node->getArgs()[0])) {
            return new SpecifiedTypes();
        }
    
        $arg = $node->getArgs()[0]->value;
        $arrayType = new ArrayType(new MixedType(true), new MixedType(true));
        $newType = new IterableType(new MixedType(true), $arrayType);

        return $this->typeSpecifier->create($arg, $newType, $context, $scope);
    }
}
