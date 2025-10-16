<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type;

use Drupal\Component\Assertion\Inspector;
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
          // 'assertTraverasble' is deprecated.
          'assertAll',
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
          'assertAllNumeric',
          'assertAllMatch',
          'assertAllRegularExpressionMatch',
          'assertAllObjects',
        ];

        return in_array($staticMethodReflection->getName(), $implemented_methods, true);
    }

    public function specifyTypes(MethodReflection $staticMethodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        return match ($staticMethodReflection->getName()) {
            default => new SpecifiedTypes(),
            'assertAll' => $this->specifyAssertAll($staticMethodReflection, $node, $scope, $context),
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
            'assertAllNumeric' => $this->specifyAssertAllNumeric($staticMethodReflection, $node, $scope, $context),
            'assertAllMatch' => $this->specifyAssertAllMatch($staticMethodReflection, $node, $scope, $context),
            'assertAllRegularExpressionMatch' => $this->specifyAssertAllRegularExpressionMatch($staticMethodReflection, $node, $scope, $context),
            'assertAllObjects' => $this->specifyAssertAllObjects($staticMethodReflection, $node, $scope, $context),
        };
    }

    /**
     * @see Drupal\Component\Assertion\Inspector::assertAll()
     */
    private function specifyAssertAll(MethodReflection $staticMethodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        $callableArg = $node->getArgs()[0]->value;
        $callableType = $scope->getType($callableArg);

        if (!$callableType->isCallable()->yes()) {
            return new SpecifiedTypes();
        }

        $traversableArg = $node->getArgs()[1]->value;
        $traversableType = $scope->getType($traversableArg);

        // If it is already not mixed (narrowed by other code, like
        // '::assertAllArray()'), we could not provide any additional
        // information. We can only narrow this method to 'array<mixed, mixed>'.
        if (!$traversableType->equals(new MixedType())) {
            return new SpecifiedTypes();
        }

        // In a negation context, we cannot precisely narrow types because we do
        // not know the exact logic of the callable function. This means we
        // cannot safely return 'mixed~iterable' since the value might still be
        // a valid iterable.
        //
        // For example, a negation context with an 'is_string(...)' callable
        // does not necessarily mean that the value cannot be an
        // 'iterable<int>'. In such cases, it is safer to skip type narrowing
        // altogether to prevent introducing new bugs into the code.
        if ($context->false()) {
            return new SpecifiedTypes();
        }

        $newType = new IterableType(new MixedType(), new MixedType());

        return $this->typeSpecifier->create($traversableArg, $newType, $context, $scope);
    }

    /**
     * @see Drupal\Component\Assertion\Inspector::assertAllStrings()
     */
    private function specifyAssertAllStrings(MethodReflection $staticMethodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        $traversableArg = $node->getArgs()[0]->value;
        $newType = new IterableType(new MixedType(), new StringType());

        return $this->typeSpecifier->create($traversableArg, $newType, $context, $scope);
    }

    /**
     * @see Drupal\Component\Assertion\Inspector::assertAllStringable()
     */
    private function specifyAssertAllStringable(MethodReflection $staticMethodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        $traversableArg = $node->getArgs()[0]->value;
        // Drupal considers string as part of "stringable" as well.
        $stringableType = TypeCombinator::union(new ObjectType(Stringable::class), new StringType());
        $newType = new IterableType(new MixedType(), $stringableType);

        return $this->typeSpecifier->create($traversableArg, $newType, $context, $scope);
    }

    /**
     * @see Drupal\Component\Assertion\Inspector::assertAllArrays()
     */
    private function specifyAssertAllArrays(MethodReflection $staticMethodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        $traversableArg = $node->getArgs()[0]->value;
        $arrayType = new ArrayType(new MixedType(), new MixedType());
        $newType = new IterableType(new MixedType(), $arrayType);

        return $this->typeSpecifier->create($traversableArg, $newType, $context, $scope);
    }

    /**
     * @see Drupal\Component\Assertion\Inspector::assertStrictArray()
     */
    private function specifyAssertStrictArray(MethodReflection $staticMethodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        $traversableArg = $node->getArgs()[0]->value;
        $newType = new ArrayType(
            // In Drupal, 'strict arrays' are defined as arrays whose
            // indexes consist of integers that are equal to or greater
            // than 0.
            IntegerRangeType::createAllGreaterThanOrEqualTo(0),
            new MixedType(),
        );

        return $this->typeSpecifier->create($traversableArg, $newType, $context, $scope);
    }

    /**
     * @see Drupal\Component\Assertion\Inspector::assertAllStrictArrays()
     */
    private function specifyAssertAllStrictArrays(MethodReflection $staticMethodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        $traversableArg = $node->getArgs()[0]->value;
        $newType = new IterableType(
            new MixedType(),
            new ArrayType(
                IntegerRangeType::createAllGreaterThanOrEqualTo(0),
                new MixedType(),
            ),
        );

        return $this->typeSpecifier->create($traversableArg, $newType, $context, $scope);
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
            foreach ($argType->getConstantStrings() as $stringType) {
                $keys[] = $stringType->getValue();
            }
        }

        // @see ArrayKeyExistsFunctionTypeSpecifyingExtension.
        $possibleTypes = [
            new ArrayType(new MixedType(), new MixedType())
        ];
        foreach ($keys as $key) {
            $possibleTypes[] = new HasOffsetType(new ConstantStringType($key));
        }

        $newType = new IterableType(
            new MixedType(),
            TypeCombinator::intersect(...$possibleTypes),
        );

        return $this->typeSpecifier->create($traversableArg, $newType, $context, $scope);
    }

    /**
     * @see Drupal\Component\Assertion\Inspector::assertAllIntegers()
     */
    private function specifyAssertAllIntegers(MethodReflection $staticMethodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        $traversableArg = $node->getArgs()[0]->value;
        $newType = new IterableType(new MixedType(), new IntegerType());

        return $this->typeSpecifier->create($traversableArg, $newType, $context, $scope);
    }

    /**
     * @see Drupal\Component\Assertion\Inspector::assertAllFloat()
     */
    private function specifyAssertAllFloat(MethodReflection $staticMethodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        $traversableArg = $node->getArgs()[0]->value;
        $newType = new IterableType(new MixedType(), new FloatType());

        return $this->typeSpecifier->create($traversableArg, $newType, $context, $scope);
    }

    /**
     * @see Drupal\Component\Assertion\Inspector::assertAllCallable()
     */
    private function specifyAssertAllCallable(MethodReflection $staticMethodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        $traversableArg = $node->getArgs()[0]->value;
        $newType = new IterableType(new MixedType(), new CallableType());

        return $this->typeSpecifier->create($traversableArg, $newType, $context, $scope);
    }

    /**
     * @see Drupal\Component\Assertion\Inspector::assertAllNotEmpty()
     */
    private function specifyAssertAllNotEmpty(MethodReflection $staticMethodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        $traversableArg = $node->getArgs()[0]->value;
        $nonEmptyTypes = [
            new NonEmptyArrayType(),
            new ObjectType('object'),
            new IntersectionType([new StringType(), new AccessoryNonEmptyStringType()]),
            IntegerRangeType::createAllGreaterThan(0),
            IntegerRangeType::createAllSmallerThan(0),
            new FloatType(),
            new ResourceType(),
        ];
        $newType = new IterableType(new MixedType(), new UnionType($nonEmptyTypes));

        return $this->typeSpecifier->create($traversableArg, $newType, $context, $scope);
    }

    /**
     * @see Drupal\Component\Assertion\Inspector::assertAllNumeric()
     */
    private function specifyAssertAllNumeric(MethodReflection $staticMethodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        $traversableArg = $node->getArgs()[0]->value;
        $newType = new IterableType(new MixedType(), new UnionType([new IntegerType(), new FloatType()]));

        return $this->typeSpecifier->create($traversableArg, $newType, $context, $scope);
    }

    /**
     * @see Drupal\Component\Assertion\Inspector::assertAllMatch()
     */
    private function specifyAssertAllMatch(MethodReflection $staticMethodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        $traversableArg = $node->getArgs()[1]->value;
        $newType = new IterableType(new MixedType(), new StringType());

        return $this->typeSpecifier->create($traversableArg, $newType, $context, $scope);
    }

    /**
     * @see Drupal\Component\Assertion\Inspector::assertAllRegularExpressionMatch()
     */
    private function specifyAssertAllRegularExpressionMatch(MethodReflection $staticMethodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        $traversableArg = $node->getArgs()[1]->value;
        // Drupal treats any non-string input in traversable as invalid
        // value, so it is possible to narrow type here.
        $newType = new IterableType(new MixedType(), new StringType());

        return $this->typeSpecifier->create($traversableArg, $newType, $context, $scope);
    }

    /**
     * @see Drupal\Component\Assertion\Inspector::assertAllObjects()
     */
    private function specifyAssertAllObjects(MethodReflection $staticMethodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        $args = $node->getArgs();
        $objectTypes = [];
        foreach ($args as $delta => $arg) {
            if ($delta === 0) {
                continue;
            }

            $argType = $scope->getType($arg->value);
            foreach ($argType->getConstantStrings() as $stringType) {
                if ($stringType->isClassString()->yes()) {
                    $objectTypes[] = new ObjectType($stringType->getValue());
                }
            }
        }

        $traversableArg = $node->getArgs()[0]->value;
        $newType = new IterableType(new MixedType(), TypeCombinator::union(...$objectTypes));

        return $this->typeSpecifier->create($traversableArg, $newType, $context, $scope);
    }
}
