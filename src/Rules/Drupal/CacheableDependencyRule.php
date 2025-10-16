<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

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

        $traversableArg = $args[0]->value;
        $object = $scope->getType($traversableArg);

        // We need to check if isInstanceOf method exists as phpstan returns
        // MixedType for unknown objects.
        if (method_exists($object, 'isInstanceOf') && $object->isInstanceOf('Drupal\Core\Cache\CacheableDependencyInterface')->yes()) {
            return [];
        }

        return [
            RuleErrorBuilder::message('Calling addCacheableDependency($object) when $object does not implement CacheableDependencyInterface effectively disables caching and should be avoided.')
                ->identifier('cacheable.dependency')
                ->build(),
        ];
    }
}
