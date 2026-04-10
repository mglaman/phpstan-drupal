<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ExtendedMethodReflection;
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
        // LHS must be a property fetch on $this.
        if (!$node->var instanceof Node\Expr\PropertyFetch) {
            return [];
        }
        if (!$node->var->var instanceof Node\Expr\Variable || $node->var->var->name !== 'this') {
            return [];
        }

        // RHS must be a method call named 'get'.
        if (!$node->expr instanceof Node\Expr\MethodCall) {
            return [];
        }
        if (!$node->expr->name instanceof Identifier || $node->expr->name->toString() !== 'get') {
            return [];
        }

        // Must be inside __construct.
        $scopeFunction = $scope->getFunction();
        if ($scopeFunction === null) {
            return [];
        }
        if (!$scopeFunction instanceof ExtendedMethodReflection) {
            return [];
        }
        if ($scopeFunction->getName() !== '__construct') {
            return [];
        }

        // The receiver of ->get() must be LoggerChannelFactoryInterface.
        $receiverType = $scope->getType($node->expr->var);
        $factoryType = new ObjectType(LoggerChannelFactoryInterface::class);
        if (!$factoryType->isSuperTypeOf($receiverType)->yes()) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                'Logger assigned from LoggerChannelFactory will break serialization. Inject a named logger channel service directly (e.g. @logger.channel.my_channel) instead.'
            )
                ->identifier('loggerFromFactory.propertyAssignment')
                ->tip('See https://www.drupal.org/node/3038430')
                ->build(),
        ];
    }
}
