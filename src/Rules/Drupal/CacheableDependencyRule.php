<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<Node\Expr\StaticCall>
 */
class CacheableDependencyRule implements Rule
{

    public function getNodeType(): string
    {
        return Node\Expr\MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $nodeFinder = new NodeFinder();
        $method = $nodeFinder->findFirst($class->stmts, static function (Node $node) {
            return $node instanceof Node\Stmt\ClassMethod && $node->name->toString() === 'addCacheableDependency';
        });
        if (!$method instanceof Node\Stmt\ClassMethod) {
            return [];
        }

        $args = $node->getArgs();
        $traversableArg = $args[0]->value;
        $object = $scope->getType($traversableArg);

        // We need to check if isInstanceOf method exists as phpstan returns
        // MixedType for unknown objects.
        if (method_exists($object, 'isInstanceOf') && $object->isInstanceOf('Drupal\Core\Cache\CacheableDependencyInterface')) {
            return [];
        }
        return [
            'Calling addCacheableDependency($object) when $object does not implement CacheableDependencyInterface effectively disables caching and should be avoided.',
        ];
    }
}
