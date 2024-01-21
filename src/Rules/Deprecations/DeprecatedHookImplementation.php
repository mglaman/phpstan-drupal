<?php

namespace mglaman\PHPStanDrupal\Rules\Deprecations;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function basename;
use function explode;
use function strlen;
use function substr_replace;

/**
 * @implements Rule<Function_>
 */
class DeprecatedHookImplementation implements Rule
{

    protected ReflectionProvider $reflectionProvider;

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
            // @todo replace this hardcoded logic with something more intelligent and extensible.
            if ($hook_name === 'hook_field_widget_form_alter') {
                return $this->buildError(
                    $function_name,
                    $hook_name,
                    'in drupal:9.2.0 and is removed from drupal:10.0.0. Use hook_field_widget_single_element_form_alter instead.'
                );
            }
            if (str_starts_with($hook_name, 'hook_field_widget_') && str_ends_with($hook_name, '_form_alter')) {
                return $this->buildError(
                    $function_name,
                    'hook_field_widget_WIDGET_TYPE_form_alter',
                    'in drupal:9.2.0 and is removed from drupal:10.0.0. Use hook_field_widget_single_element_WIDGET_TYPE_form_alter instead.'
                );
            }
            if ($hook_name === 'hook_field_widget_multivalue_form_alter') {
                return $this->buildError(
                    $function_name,
                    $hook_name,
                    'in drupal:9.2.0 and is removed from drupal:10.0.0. Use hook_field_widget_complete_form_alter instead.'
                );
            }
            if (str_starts_with($hook_name, 'hook_field_widget_multivalue_') && str_ends_with($hook_name, '_form_alter')) {
                return $this->buildError(
                    $function_name,
                    'hook_field_widget_multivalue_WIDGET_TYPE_form_alter',
                    'in drupal:9.2.0 and is removed from drupal:10.0.0. Use hook_field_widget_complete_WIDGET_TYPE_form_alter instead.'
                );
            }

            return [];
        }

        $reflection = $this->reflectionProvider->getFunction($hook_name_node, $scope);
        if (!$reflection->isDeprecated()->yes()) {
            return [];
        }

        return $this->buildError($function_name, $hook_name, $reflection->getDeprecatedDescription());
    }

    private function buildError(string $function_name, string $hook_name, ?string $deprecated_description): array
    {
        $deprecated_description = $deprecated_description !== null ? " $deprecated_description" : ".";
        return [
            RuleErrorBuilder::message(
                "Function $function_name implements $hook_name which is deprecated$deprecated_description",
            )->build()
        ];
    }
}
