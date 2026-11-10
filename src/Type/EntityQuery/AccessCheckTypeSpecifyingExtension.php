<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type\EntityQuery;

use Drupal\Core\Entity\Query\QueryInterface;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
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
        // This runs for every query method call, such as each separate
        // $query->condition() statement. Only a chain that includes
        // accessCheck() needs types, so check the method names first.
        if (!$this->chainCallsAccessCheck($node)) {
            return new SpecifiedTypes([]);
        }

        $hasAccessCheck = false;
        $call = $node;
        while (true) {
            if ($this->isAccessCheckCall($call)) {
                $hasAccessCheck = true;
            }
            $receiver = $call->var;
            if ((!$receiver instanceof MethodCall && !$receiver instanceof NullsafeMethodCall)
                || !$scope->getType($receiver) instanceof EntityQueryType
            ) {
                break;
            }
            $call = $receiver;
        }
        if (!$hasAccessCheck) {
            return new SpecifiedTypes([]);
        }

        // A call like \Drupal::entityQuery('node') returns a new query each
        // time, so narrowing it would mark later, unrelated queries as
        // checked. Variables, properties and array elements keep the query.
        $query = $call->var;
        if ($query instanceof CallLike) {
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

    private function chainCallsAccessCheck(MethodCall $node): bool
    {
        $call = $node;
        while ($call instanceof MethodCall || $call instanceof NullsafeMethodCall) {
            if ($this->isAccessCheckCall($call)) {
                return true;
            }
            $call = $call->var;
        }
        return false;
    }

    private function isAccessCheckCall(MethodCall|NullsafeMethodCall $call): bool
    {
        return $call->name instanceof Identifier && $call->name->toLowerString() === 'accesscheck';
    }
}
