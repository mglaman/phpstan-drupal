<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type;

use Drupal\Core\Access\AccessResult;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Node\MatchExpressionNode;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\StaticMethodTypeSpecifyingExtension;

final class AccessResultConditionTypeSpecifyingExtension implements StaticMethodTypeSpecifyingExtension, TypeSpecifierAwareExtension
{
    /** @var TypeSpecifier */
    private $typeSpecifier;

    public function getClass(): string
    {
        return AccessResult::class;
    }

    public function isMethodSupported(
        MethodReflection $methodReflection,
        MethodCall $node,
        TypeSpecifierContext $context
    ): bool {
        return in_array(
            $methodReflection->getName(),
            ['allowedIf', 'forbiddenIf'],
            true
        );
    }

    public function specifyTypes(
        MethodReflection $staticMethodReflection,
        StaticCall $node,
        Scope $scope,
        TypeSpecifierContext $context
    ): SpecifiedTypes {
        $args = $node->getArgs();
        if (count($args) === 0) {
            return new SpecifiedTypes([]);
        }

        $expr = new BooleanOr(
            new Identical(
                $args[0]->value,
                new ConstFetch(new Name('true'))
            ),
            new Identical(
                $args[0]->value,
                new ConstFetch(new Name('false'))
            )
        );
        $stop = null;
        return $this->typeSpecifier->specifyTypesInCondition(
            $scope,
            $expr,
            $context
        );
    }

    public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
    {
        $this->typeSpecifier = $typeSpecifier;
    }

    public function isStaticMethodSupported(
        MethodReflection $staticMethodReflection,
        StaticCall $node,
        TypeSpecifierContext $context
    ): bool {
        $stop = null;
        return in_array(
            $staticMethodReflection->getName(),
            ['allowedIf', 'forbiddenIf'],
            true
        );
    }
}
