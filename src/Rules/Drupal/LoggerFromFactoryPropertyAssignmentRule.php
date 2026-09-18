<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/**
 * @implements Rule<Node\Expr\Assign>
 */
final class LoggerFromFactoryPropertyAssignmentRule implements Rule
{

    public function getNodeType(): string
    {
        return Node\Expr\Assign::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$scope->isInClass()) {
            return [];
        }
        if (!$scope->getClassReflection()->hasTraitUse(DependencySerializationTrait::class)) {
            return [];
        }

        $scopeFunction = $scope->getFunction();
        if ($scopeFunction === null || $scopeFunction->getName() !== '__construct') {
            return [];
        }

        if (!$node->var instanceof Node\Expr\PropertyFetch) {
            return [];
        }
        if (!$node->var->var instanceof Node\Expr\Variable || $node->var->var->name !== 'this') {
            return [];
        }

        if (!$node->expr instanceof Node\Expr\MethodCall) {
            return [];
        }
        if (!$node->expr->name instanceof Identifier || $node->expr->name->toString() !== 'get') {
            return [];
        }

        $receiverType = $scope->getType($node->expr->var);
        $factoryType = new ObjectType(LoggerChannelFactoryInterface::class);
        if (!$factoryType->isSuperTypeOf($receiverType)->yes()) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                'Logger assigned from LoggerChannelFactory in a class using DependencySerializationTrait will break serialization. Inject a named logger channel service directly (e.g. @logger.channel.my_channel) instead.'
            )
                ->identifier('loggerFromFactory.propertyAssignment')
                ->build(),
        ];
    }
}
