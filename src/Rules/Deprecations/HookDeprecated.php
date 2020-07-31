<?php declare(strict_types=1);

namespace PHPStan\Rules\Deprecations;

use DrupalFinder\DrupalFinder;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\Broker;
use PHPStan\Drupal\Extension;
use PHPStan\Drupal\ExtensionDiscovery;
use PHPStan\Rules\Rule;
use PHPStan\ShouldNotHappenException;

class HookDeprecated implements Rule
{

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

        // @todo support invokeAllDeprecated, invokeDeprecated
        if ($node->name->toString() !== 'alterDeprecated') {
            return [];
        }
        $args = $node->args;
        if (count($args) < 3) {
            throw new ShouldNotHappenException('alterDeprecated was called with missing arguments.');
        }
        $arg_description = $args[0]->value;
        assert($arg_description instanceof Node\Scalar\String_);
        // @todo this could be an array of hook types.
        $arg_type = $args[1]->value;
        assert($arg_type instanceof Node\Scalar\String_);

        $deprecation_description = $arg_description->value;
        $hook_type = $arg_type->value;

        /** @var Extension[] $modules */
        $modules = $GLOBALS['drupalModuleData'];

        $errors = [];
        foreach ($modules as $module) {
            $hook = "{$module->getName()}_{$hook_type}_alter";
            if (\function_exists($hook)) {
                $errors = [sprintf(
                    "Call to deprecated alter hook %s with %s():\n%s",
                    $hook_type,
                    $hook,
                    $deprecation_description
                )];
            }
        }
        return $errors;
    }
}
