<?php declare(strict_types=1);

namespace PHPStan\Rules\Deprecations;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\Broker;
use PHPStan\Rules\Rule;
use PHPStan\ShouldNotHappenException;

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
        $args = $node->args;
        if (count($args) < 3) {
            throw new ShouldNotHappenException('alterDeprecated was called with missing arguments.');
        }
        $arg_description = $args[0]->value;
        assert($arg_description instanceof Node\Scalar\String_);
        $arg_type = $args[1]->value;
        assert($arg_type instanceof Node\Scalar\String_);

        $deprecation_description = $arg_description->value;
        $hook_type = $arg_type->value;
        return [sprintf(
            "Call to deprecated alter hook %s:\n%s",
            $hook_type,
            $deprecation_description
        )];
    }
}
