<?php declare(strict_types=1);

namespace PHPStan\Rules\Deprecations;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\Broker;
use PHPStan\Rules\Rule;

class HookDeprecated implements Rule
{

    /** @var Broker */
    private $broker;
    public function __construct(Broker $broker)
    {
        $this->broker = $broker;
    }

    public function getNodeType(): string
    {
        return Node\Expr\MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {

        assert($node instanceof Node\Expr\MethodCall);
        if (!$node->name instanceof Identifier) {
            return [];
        }
        if ($node->name->toString() !== 'alterDeprecated') {
            return [];
        }
        $function = $scope->getFunction();
        $class = $scope->getClassReflection();
        $stop = null;
        // TODO: Implement processNode() method.
    }
}
