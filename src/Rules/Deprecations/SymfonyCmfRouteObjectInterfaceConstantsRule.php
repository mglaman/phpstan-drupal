<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Deprecations;

use Drupal\Core\Routing\RouteObjectInterface;
use mglaman\PHPStanDrupal\Internal\DeprecatedScopeCheck;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

final class SymfonyCmfRouteObjectInterfaceConstantsRule implements Rule
{

    public function getNodeType(): string
    {
        return Node\Expr\ClassConstFetch::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof Node\Expr\ClassConstFetch);
        if (!$node->name instanceof Node\Identifier) {
            return [];
        }
        if (!$node->class instanceof Node\Name) {
            return [];
        }
        $constantName = $node->name->name;
        $className = $node->class;
        $classType = $scope->resolveTypeByName($className);
        if (!$classType->hasConstant($constantName)->yes()) {
            return [];
        }
        if (DeprecatedScopeCheck::inDeprecatedScope($scope)) {
            return [];
        }
        [$major, $minor] = explode('.', \Drupal::VERSION, 3);
        if ($major !== '9' && (int) $minor > 1) {
            return [];
        }
        $cmfRouteObjectInterfaceType = new ObjectType(\Symfony\Cmf\Component\Routing\RouteObjectInterface::class);
        if (!$classType->isSuperTypeOf($cmfRouteObjectInterfaceType)->yes()) {
            return [];
        }

        $coreRouteObjectInterfaceType = new ObjectType(RouteObjectInterface::class);
        if (!$coreRouteObjectInterfaceType->hasConstant($constantName)->yes()) {
            return [
                RuleErrorBuilder::message(
                    sprintf('The core dependency symfony-cmf/routing is deprecated and %s::%s is not supported.', $className, $constantName)
                )->tip('Change record: https://www.drupal.org/node/3151009')->build(),
            ];
        }

        return [
            RuleErrorBuilder::message(
                sprintf('%s::%s is deprecated and removed in Drupal 10. Use \Drupal\Core\Routing\RouteObjectInterface::%2$s instead.', $className, $constantName)
            )->tip('Change record: https://www.drupal.org/node/3151009')->build(),
        ];
    }
}
