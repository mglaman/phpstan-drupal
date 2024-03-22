<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use Drupal\Core\Access\AccessResult;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements Rule<Node\Expr\StaticCall>
 */
final class AccessResultConditionRule implements Rule
{

    /** @var bool */
    private $treatPhpDocTypesAsCertain;

    /**
     * @param bool $treatPhpDocTypesAsCertain
     */
    public function __construct($treatPhpDocTypesAsCertain)
    {
        $this->treatPhpDocTypesAsCertain = $treatPhpDocTypesAsCertain;
    }

    public function getNodeType(): string
    {
        return Node\Expr\StaticCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Node\Identifier) {
            return [];
        }
        $methodName = $node->name->toString();
        if (!in_array($methodName, ['allowedIf', 'forbiddenIf'], true)) {
            return [];
        }
        if (!$node->class instanceof Node\Name) {
            return [];
        }
        $className = $scope->resolveName($node->class);
        if ($className !== AccessResult::class) {
            return [];
        }
        $args = $node->getArgs();
        if (count($args) === 0) {
            return [];
        }
        $condition = $args[0]->value;
        if (!$condition instanceof Node\Expr\BinaryOp\Identical && !$condition instanceof Node\Expr\BinaryOp\NotIdentical) {
            return [];
        }
        $conditionType = $this->treatPhpDocTypesAsCertain ? $scope->getType($condition) : $scope->getNativeType($condition);
        $bool = $conditionType->toBoolean();

        if ($bool->isTrue()->or($bool->isFalse())->yes()) {
            $leftType = $this->treatPhpDocTypesAsCertain ? $scope->getType($condition->left) : $scope->getNativeType($condition->left);
            $rightType = $this->treatPhpDocTypesAsCertain ? $scope->getType($condition->right) : $scope->getNativeType($condition->right);

            return [
                RuleErrorBuilder::message(sprintf(
                    'Strict comparison using %s between %s and %s will always evaluate to %s.',
                    $condition->getOperatorSigil(),
                    $leftType->describe(VerbosityLevel::value()),
                    $rightType->describe(VerbosityLevel::value()),
                    $bool->describe(VerbosityLevel::value()),
                ))->identifier(sprintf('%s.alwaysFalse', $condition instanceof Node\Expr\BinaryOp\Identical ? 'identical' : 'notIdentical'))->build(),
            ];
        }
        return [];
    }
}
