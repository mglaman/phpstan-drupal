<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<Node\Expr\StaticCall>
 */
class GlobalDrupalDependencyInjectionRule implements Rule
{
    public function getNodeType(): string
    {
        return Node\Expr\StaticCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        // Only check static calls to \Drupal
        if (!($node->class instanceof Node\Name\FullyQualified) || (string) $node->class !== 'Drupal') {
            return [];
        }
        // Do not raise if called inside a trait.
        if (!$scope->isInClass() || $scope->isInTrait()) {
            return [];
        }
        $scopeClassReflection = $scope->getClassReflection();

        // Enums cannot have dependency injection.
        if ($scopeClassReflection->isEnum()) {
            return [];
        }

        $allowed_list = [
            // Ignore tests.
            'PHPUnit\Framework\Test',
            // Typed data objects cannot use dependency injection.
            'Drupal\Core\TypedData\TypedDataInterface',
            // Render elements cannot use dependency injection.
            'Drupal\Core\Render\Element\ElementInterface',
            'Drupal\Core\Render\Element\FormElementInterface',
            'Drupal\config_translation\FormElement\ElementInterface',
            // Entities don't use services for now
            // @see https://www.drupal.org/project/drupal/issues/2913224
            'Drupal\Core\Entity\EntityInterface',
            // Stream wrappers are only registered as a service for their tags
            // and cannot use dependency injection. Function calls like
            // file_exists, stat, etc. will construct the class directly.
            'Drupal\Core\StreamWrapper\StreamWrapperInterface',
            // Ignore Nightwatch test setup classes.
            'Drupal\TestSite\TestSetupInterface',
        ];

        foreach ($allowed_list as $item) {
            if ($scopeClassReflection->implementsInterface($item)) {
                return [];
            }
        }

        $scopeFunction = $scope->getFunction();
        if ($scopeFunction === null) {
            return [];
        }
        if (!$scopeFunction instanceof ExtendedMethodReflection) {
            return [];
        }
        if ($scopeFunction->isStatic()) {
            return [];
        }

        return [
            '\Drupal calls should be avoided in classes, use dependency injection instead'
        ];
    }
}
