<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/**
 * @implements Rule<Node\Expr\MethodCall>
 */
class CacheableDependencyRule implements Rule
{

    public function getNodeType(): string
    {
        return Node\Expr\MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Identifier || $node->name->toString() !== 'addCacheableDependency') {
            return [];
        }

        $args = $node->getArgs();
        if (count($args) === 0) {
            return [];
        }

        $dependencyArg = $args[0]->value;
        $object = $scope->getType($dependencyArg);

        $interfaceType = new ObjectType('Drupal\Core\Cache\CacheableDependencyInterface');
        $implementsInterface = $interfaceType->isSuperTypeOf($object);

        if (!$implementsInterface->no()) {
            return [];
        }

        return [
            RuleErrorBuilder::message('Calling addCacheableDependency($object) when $object does not implement CacheableDependencyInterface effectively disables caching and should be avoided.')
                ->identifier('cacheable.dependency')
                ->build(),
        ];
    }
}
