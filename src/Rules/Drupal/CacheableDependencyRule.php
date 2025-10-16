<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Plugin\Context\ContextInterface;
use Drupal\Core\Render\RendererInterface;

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

        $receiverType = $scope->getType($node->var);

        $allowedInterfaces = [
            RefinableCacheableDependencyInterface::class => 0,
            CacheableResponseInterface::class => 0,
            ContextInterface::class => 0,
            RendererInterface::class => 1,
        ];

        $argumentIndex = null;

        foreach ($allowedInterfaces as $interfaceName => $argPosition) {
            $interfaceType = new ObjectType($interfaceName);
            if (!$interfaceType->isSuperTypeOf($receiverType)->no()) {
                $argumentIndex = $argPosition;
                break;
            }
        }

        if ($argumentIndex === null) {
            return [];
        }

        $args = $node->getArgs();
        if (count($args) <= $argumentIndex) {
            return [];
        }

        $dependencyArg = $args[$argumentIndex]->value;
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
