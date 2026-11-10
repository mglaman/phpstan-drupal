<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type\EntityQuery;

use Drupal\Core\Entity\Query\QueryInterface;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\MethodReflection;
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

    /**
     * Supports every query method, because accessCheck() can be anywhere in a
     * chain like $query->accessCheck(FALSE)->condition('status', 1);
     */
    public function isMethodSupported(
        MethodReflection $methodReflection,
        MethodCall $node,
        TypeSpecifierContext $context
    ): bool {
        return true;
    }

    /**
     * Marks the query a method chain starts from as access checked.
     *
     * Query methods return the query itself, so in
     * $query->condition('status', 1)->accessCheck(FALSE); the accessCheck()
     * call applies to $query, not only to the value of the chain.
     */
    public function specifyTypes(
        MethodReflection $methodReflection,
        MethodCall $node,
        Scope $scope,
        TypeSpecifierContext $context
    ): SpecifiedTypes {
        $hasAccessCheck = false;
        $call = $node;
        while (true) {
            if ($call->name instanceof Identifier && $call->name->toLowerString() === 'accesscheck') {
                $hasAccessCheck = true;
            }
            if (!$call->var instanceof MethodCall || !$scope->getType($call->var) instanceof EntityQueryType) {
                break;
            }
            $call = $call->var;
        }
        if (!$hasAccessCheck) {
            return new SpecifiedTypes([]);
        }

        // Only a variable or property keeps the query. A call like
        // \Drupal::entityQuery('node') returns a new query each time, so
        // narrowing it would mark later, unrelated queries as checked.
        $query = $call->var;
        if (!$query instanceof Variable && !$query instanceof PropertyFetch && !$query instanceof StaticPropertyFetch) {
            return new SpecifiedTypes([]);
        }
        $queryType = $scope->getType($query);
        if (!$queryType instanceof EntityQueryType) {
            return new SpecifiedTypes([]);
        }
        return $this->typeSpecifier->create(
            $query,
            $queryType->withAccessCheck(),
            TypeSpecifierContext::createTruthy(),
            $scope
        )->setAlwaysOverwriteTypes();
    }
}
