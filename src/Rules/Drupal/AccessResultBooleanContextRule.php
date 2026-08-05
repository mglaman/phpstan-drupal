<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use Drupal\Core\Access\AccessResultInterface;
use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\BinaryOp\LogicalAnd;
use PhpParser\Node\Expr\BinaryOp\LogicalOr;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\Cast\Bool_;
use PhpParser\Node\Expr\Empty_;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Stmt\Do_;
use PhpParser\Node\Stmt\ElseIf_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\While_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/**
 * Reports access results that are evaluated in a boolean context.
 *
 * An AccessResultInterface object is always truthy, so negating it or using it
 * as a condition never reflects whether access was granted. The result must be
 * inspected with ->isAllowed(), ->isForbidden(), or ->isNeutral() instead.
 *
 * @implements Rule<Node>
 */
final class AccessResultBooleanContextRule implements Rule
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
        return Node::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $accessResultType = new ObjectType(AccessResultInterface::class);
        $errors = [];
        foreach ($this->getConditionExpressions($node) as $expr) {
            $type = $this->treatPhpDocTypesAsCertain ? $scope->getType($expr) : $scope->getNativeType($expr);
            if (!$accessResultType->isSuperTypeOf($type)->yes()) {
                continue;
            }
            $errors[] = RuleErrorBuilder::message(
                'Access result used in a boolean context. An access result object is always truthy; use ->isAllowed(), ->isForbidden(), or ->isNeutral() to inspect it.'
            )->identifier('drupal.accessResultBoolean')->line($expr->getStartLine())->build();
        }

        return $errors;
    }

    /**
     * Returns the expressions a node evaluates for truthiness.
     *
     * @return \PhpParser\Node\Expr[]
     */
    private function getConditionExpressions(Node $node): array
    {
        switch (true) {
            case $node instanceof BooleanNot:
            case $node instanceof Bool_:
            case $node instanceof Empty_:
                return [$node->expr];
            case $node instanceof If_:
            case $node instanceof ElseIf_:
            case $node instanceof While_:
            case $node instanceof Do_:
            case $node instanceof Ternary:
                return [$node->cond];
            case $node instanceof BooleanAnd:
            case $node instanceof BooleanOr:
            case $node instanceof LogicalAnd:
            case $node instanceof LogicalOr:
                return [$node->left, $node->right];
            default:
                return [];
        }
    }
}
