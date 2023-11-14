<?php

namespace mglaman\PHPStanDrupal\Rules\Deprecations;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

class DeprecatedHookImplementation implements Rule
{

    /**
     * @var \PHPStan\Reflection\ReflectionProvider
     */
    protected $reflectionProvider;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }

    public function getNodeType(): string
    {
        return Function_::class;
    }

    public function processNode(Node $node, Scope $scope) : array
    {
        assert($node instanceof Function_);
        if (!str_ends_with($scope->getFile(), ".module") && !str_ends_with($scope->getFile(), ".inc")) {
            return [];
        }

        // We want both name.module and name.views.inc, to resolve to name.
        $module_name = explode(".", basename($scope->getFile()))[0];

        // Hooks start with their own module's name.
        if (!str_starts_with($node->name->toString(), "{$module_name}_")) {
            return [];
        }

        $function_name = $node->name->toString();
        $hook_name = substr_replace($function_name, "hook", 0, strlen($module_name));

        $hook_name_node = new Name($hook_name);
        if (!$this->reflectionProvider->hasFunction($hook_name_node, $scope)) {
            return [];
        }

        $reflection = $this->reflectionProvider->getFunction($hook_name_node, $scope);
        if (!$reflection->isDeprecated()->yes()) {
            return [];
        }

        $deprecation_description = $reflection->getDeprecatedDescription();
        $deprecation_message = $deprecation_description !== null ? " $deprecation_description" : ".";

        return [
            RuleErrorBuilder::message(
                "Function $function_name implements $hook_name which is deprecated$deprecation_message",
            )->build()
        ];
    }
}
