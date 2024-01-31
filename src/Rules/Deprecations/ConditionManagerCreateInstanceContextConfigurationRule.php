<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Deprecations;

use Drupal\Core\Condition\ConditionManager;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ObjectType;
use function count;

/**
 * @implements Rule<Node\Expr\MethodCall>
 */
final class ConditionManagerCreateInstanceContextConfigurationRule implements Rule
{
    public function getNodeType(): string
    {
        return Node\Expr\MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Node\Identifier) {
            return [];
        }
        if ($node->name->toString() !== 'createInstance') {
            return [];
        }
        $args = $node->getArgs();
        if (count($args) !== 2) {
            return [];
        }
        $conditionManagerType = new ObjectType(ConditionManager::class);
        $type = $scope->getType($node->var);
        if (!$conditionManagerType->isSuperTypeOf($type)->yes()) {
            return [];
        }
        $configuration = $args[1];
        $configurationType = $scope->getType($configuration->value);
        // Must be an array, return [] and allow parameter inspection rule to report error.
        if (!$configurationType instanceof ConstantArrayType) {
            return [];
        }

        foreach ($configurationType->getKeyTypes() as $keyType) {
            if ($keyType instanceof ConstantStringType && $keyType->getValue() === 'context') {
                return [
                    RuleErrorBuilder::message('Passing context values to plugins via configuration is deprecated in drupal:9.1.0 and will be removed before drupal:10.0.0. Instead, call ::setContextValue() on the plugin itself. See https://www.drupal.org/node/3120980')
                        ->line($node->getStartLine())
                        ->build()
                ];
            }
        }

        return [];
    }
}
