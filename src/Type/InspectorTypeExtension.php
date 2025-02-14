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
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\HasOffsetType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\CallableType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\IterableType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ResourceType;
use PHPStan\Type\StaticMethodTypeSpecifyingExtension;
use PHPStan\Type\StringType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
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
          // assertTraverasble
          // assertAll - looks like better to return iterable<mixed>
          'assertAllStrings',
          'assertAllStringable',
          'assertAllArrays',
          'assertStrictArray',
          'assertAllStrictArrays',
          'assertAllHaveKey',
          'assertAllIntegers',
          'assertAllFloat',
          'assertAllCallable',
          'assertAllNotEmpty',
          // assertAllNumeric
          // assertAllMatch
          // assertAllRegularExpressionMatch
          // assertAllObjects
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
            'assertAllIntegers' => $this->specifyAssertAllIntegers($staticMethodReflection, $node, $scope, $context),
            'assertAllFloat' => $this->specifyAssertAllFloat($staticMethodReflection, $node, $scope, $context),
            'assertAllCallable' => $this->specifyAssertAllCallable($staticMethodReflection, $node, $scope, $context),
            'assertAllNotEmpty' => $this->specifyAssertAllNotEmpty($staticMethodReflection, $node, $scope, $context),
        };
    }

    /**
     * @see Drupal\Component\Assertion\Inspector::assertAllStrings()
     */
    private function specifyAssertAllStrings(MethodReflection $staticMethodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        $newType = new IterableType(new MixedType(true), new StringType());

        return $this->typeSpecifier->create($node->getArgs()[0]->value, $newType, TypeSpecifierContext::createTruthy(), $scope);
    }

    /**
     * @see Drupal\Component\Assertion\Inspector::assertAllStringable()
     */
    private function specifyAssertAllStringable(MethodReflection $staticMethodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        $arg = $node->getArgs()[0]->value;

        // Drupal considers string as part of "stringable" as well.
        $stringable = TypeCombinator::union(new ObjectType(Stringable::class), new StringType());
        $newType = new IterableType(new MixedType(true), $stringable);

        return $this->typeSpecifier->create($arg, $newType, TypeSpecifierContext::createTruthy(), $scope);
    }

    /**
     * @see Drupal\Component\Assertion\Inspector::assertAllArrays()
     */
    private function specifyAssertAllArrays(MethodReflection $staticMethodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        $arg = $node->getArgs()[0]->value;
        $arrayType = new ArrayType(new MixedType(true), new MixedType(true));
        $newType = new IterableType(new MixedType(true), $arrayType);

        return $this->typeSpecifier->create($arg, $newType, TypeSpecifierContext::createTruthy(), $scope);
    }

    /**
     * @see Drupal\Component\Assertion\Inspector::assertStrictArray()
     */
    private function specifyAssertStrictArray(MethodReflection $staticMethodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        $arg = $node->getArgs()[0]->value;
        $newType = new ArrayType(
            // In Drupal, 'strict arrays' are defined as arrays whose indexes
            // consist of integers that are equal to or greater than 0.
            IntegerRangeType::createAllGreaterThanOrEqualTo(0),
            new MixedType(true),
        );

        return $this->typeSpecifier->create($arg, $newType, TypeSpecifierContext::createTruthy(), $scope);
    }

    /**
     * @see Drupal\Component\Assertion\Inspector::assertAllStrictArrays()
     */
    private function specifyAssertAllStrictArrays(MethodReflection $staticMethodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        $arg = $node->getArgs()[0]->value;
        $newType = new IterableType(
            new MixedType(true),
            new ArrayType(
                IntegerRangeType::createAllGreaterThanOrEqualTo(0),
                new MixedType(true),
            ),
        );

        return $this->typeSpecifier->create($arg, $newType, TypeSpecifierContext::createTruthy(), $scope);
    }

    /**
     * @see Drupal\Component\Assertion\Inspector::assertAllHaveKey()
     */
    private function specifyAssertAllHaveKey(MethodReflection $staticMethodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        $args = $node->getArgs();

        $traversableArg = $args[0]->value;
        $traversableType = $scope->getType($traversableArg);

        if ($traversableType->isIterable()->no()) {
            return new SpecifiedTypes();
        }

        $keys = [];
        foreach ($args as $delta => $arg) {
            if ($delta === 0) {
                continue;
            }

            $argType = $scope->getType($arg->value);
            if (!$argType instanceof ConstantStringType) {
                continue;
            }

            $keys[] = $argType->getValue();
        }

        if (empty($keys)) {
            return new SpecifiedTypes();
        }

        $keyTypes = [];
        foreach ($keys as $key) {
            $keyTypes[] = new HasOffsetType(new ConstantStringType($key));
        }

        $newArrayType = new ArrayType(
            new MixedType(true),
            new ArrayType(TypeCombinator::intersect(new MixedType(), ...$keyTypes), new MixedType(true)),
        );

        return $this->typeSpecifier->create($traversableArg, $newArrayType, TypeSpecifierContext::createTruthy(), $scope);
    }

    /**
     * @see Drupal\Component\Assertion\Inspector::assertAllIntegers()
     */
    private function specifyAssertAllIntegers(MethodReflection $staticMethodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        $arg = $node->getArgs()[0]->value;
        $newType = new IterableType(new MixedType(true), new IntegerType());

        return $this->typeSpecifier->create($arg, $newType, TypeSpecifierContext::createTruthy(), $scope);
    }

    /**
     * @see Drupal\Component\Assertion\Inspector::assertAllFloat()
     */
    private function specifyAssertAllFloat(MethodReflection $staticMethodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        $arg = $node->getArgs()[0]->value;
        $newType = new IterableType(new MixedType(true), new FloatType());

        return $this->typeSpecifier->create($arg, $newType, TypeSpecifierContext::createTruthy(), $scope);
    }

    /**
     * @see Drupal\Component\Assertion\Inspector::assertAllCallable()
     */
    private function specifyAssertAllCallable(MethodReflection $staticMethodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        $arg = $node->getArgs()[0]->value;
        $newType = new IterableType(new MixedType(true), new CallableType());

        return $this->typeSpecifier->create($arg, $newType, TypeSpecifierContext::createTruthy(), $scope);
    }

    /**
     * @see Drupal\Component\Assertion\Inspector::assertAllNotEmpty()
     */
    private function specifyAssertAllNotEmpty(MethodReflection $staticMethodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        $arg = $node->getArgs()[0]->value;

        $non_empty_types = [
            new NonEmptyArrayType(),
            new ObjectType('object'),
            new IntersectionType([new StringType(), new AccessoryNonEmptyStringType()]),
            IntegerRangeType::createAllGreaterThan(0),
            IntegerRangeType::createAllSmallerThan(0),
            new FloatType(),
            new ResourceType(),
        ];
        $newType = new IterableType(new MixedType(true), new UnionType($non_empty_types));

        return $this->typeSpecifier->create($arg, $newType, TypeSpecifierContext::createTruthy(), $scope);
    }
}
