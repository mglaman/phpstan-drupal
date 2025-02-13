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
use PHPStan\Type\Accessory\HasOffsetType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IterableType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticMethodTypeSpecifyingExtension;
use PHPStan\Type\StringType;
use PHPStan\Type\TypeCombinator;
use Stringable;
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
          'assertAllStringable',
          'assertAllArrays',
          'assertStrictArray',
          'assertAllStrictArrays',
          'assertAllHaveKey',
        ];

        return in_array($staticMethodReflection->getName(), $implemented_methods);
    }

    public function specifyTypes(MethodReflection $staticMethodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        return match ($staticMethodReflection->getName()) {
            default => throw new InvalidArgumentException(sprintf('Method %s is not yet implemented.', $staticMethodReflection->getName())),
            'assertAllStrings' => $this->specifyAssertAllStrings($staticMethodReflection, $node, $scope, $context),
            'assertAllStringable' => $this->specifyAssertAllStringable($staticMethodReflection, $node, $scope, $context),
            'assertAllArrays' => $this->specifyAssertAllArrays($staticMethodReflection, $node, $scope, $context),
            'assertStrictArray' => $this->specifyAssertStrictArray($staticMethodReflection, $node, $scope, $context),
            'assertAllStrictArrays' => $this->specifyAssertAllStrictArrays($staticMethodReflection, $node, $scope, $context),
            'assertAllHaveKey' => $this->specifyAssertAllHaveKey($staticMethodReflection, $node, $scope, $context),
        };
    }

    private function specifyAssertAllStrings(MethodReflection $staticMethodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        $newType = new IterableType(new MixedType(true), new StringType());

        return $this->typeSpecifier->create($node->getArgs()[0]->value, $newType, $context, $scope);
    }

    private function specifyAssertAllStringable(MethodReflection $staticMethodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        $arg = $node->getArgs()[0]->value;

        // Drupal considers string as part of "stringable" as well.
        $stringable = TypeCombinator::union(new ObjectType(Stringable::class), new StringType());
        $newType = new IterableType(new MixedType(true), $stringable);

        return $this->typeSpecifier->create($arg, $newType, $context, $scope);
    }

    private function specifyAssertAllArrays(MethodReflection $staticMethodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        $arg = $node->getArgs()[0]->value;
        $arrayType = new ArrayType(new MixedType(true), new MixedType(true));
        $newType = new IterableType(new MixedType(true), $arrayType);

        return $this->typeSpecifier->create($arg, $newType, $context, $scope);
    }

    private function specifyAssertStrictArray(MethodReflection $staticMethodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        $arg = $node->getArgs()[0]->value;
        $newType = new ArrayType(IntegerRangeType::createAllGreaterThanOrEqualTo(0), new MixedType(true));

        return $this->typeSpecifier->create($arg, $newType, $context, $scope);
    }

    private function specifyAssertAllStrictArrays(MethodReflection $staticMethodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        $arg = $node->getArgs()[0]->value;
        $newType = new IterableType(new MixedType(true), new ArrayType(IntegerRangeType::createAllGreaterThanOrEqualTo(0), new MixedType(true)));

        return $this->typeSpecifier->create($arg, $newType, $context, $scope);
    }

    private function specifyAssertAllHaveKey(MethodReflection $staticMethodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        $args = $node->getArgs();

        $traversableArg = $args[0]->value;
        $traversableType = $scope->getType($traversableArg);

        if (!$traversableType instanceof ArrayType) {
            return new SpecifiedTypes();
        }

        $keys = [];
        foreach ($args as $delta => $arg) {
            if ($delta === 0) {
                continue;
            }

            $argType = $scope->getType($arg->value);
            if (!$argType instanceof ConstantStringType) {
                return new SpecifiedTypes();
            }

            $keys[] = $argType->getValue();
        }

        if (empty($keys)) {
            return new SpecifiedTypes();
        }

        $itemType = $traversableType->getItemType();
        foreach ($keys as $key) {
            $itemType = TypeCombinator::intersect($itemType, new HasOffsetType(new ConstantStringType($key)));
        }

        $newArrayType = new ArrayType($traversableType->getKeyType(), $itemType);

        return $this->typeSpecifier->create($traversableArg, $newArrayType, $context, $scope);
    }
}
