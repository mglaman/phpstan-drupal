<?php declare(strict_types = 1);

namespace PHPStan\Rules\Deprecations;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\Broker;
use PHPStan\Rules\RuleLevelHelper;

class AccessDeprecatedConstant implements \PHPStan\Rules\Rule
{
    /** @var Broker */
    private $broker;
    /** @var RuleLevelHelper */
    private $ruleLevelHelper;
    public function __construct(Broker $broker, RuleLevelHelper $ruleLevelHelper)
    {
        $this->broker = $broker;
        $this->ruleLevelHelper = $ruleLevelHelper;
    }

    public function getNodeType(): string
    {
        return Node\Expr\ConstFetch::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (DeprecatedScopeHelper::isScopeDeprecated($scope)) {
            return [];
        }

        // comment is not resolved.
        $comment = $node->getDocComment();
        $constnatName = $this->broker->resolveConstantName($node->name, $scope);
        return [];
    }

}
