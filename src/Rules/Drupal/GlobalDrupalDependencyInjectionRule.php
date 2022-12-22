<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\Rule;
use PHPStan\ShouldNotHappenException;

class GlobalDrupalDependencyInjectionRule implements Rule
{
    public function getNodeType(): string
    {
        return Node\Expr\StaticCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof Node\Expr\StaticCall);

        // Only check static calls to \Drupal
        if (!($node->class instanceof Node\Name\FullyQualified) || (string) $node->class !== 'Drupal') {
            return [];
        }
        // Do not raise if called inside of a trait.
        if (!$scope->isInClass() || $scope->isInTrait()) {
            return [];
        }
        $scopeClassReflection = $scope->getClassReflection();

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
        ];

        foreach ($allowed_list as $item) {
            if ($scopeClassReflection->implementsInterface($item)) {
                return [];
            }
        }

        if ($scope->getFunctionName() === null) {
            throw new ShouldNotHappenException();
        }

        $scopeFunction = $scope->getFunction();
        if (!($scopeFunction instanceof MethodReflection)) {
            throw new ShouldNotHappenException();
        }
        // Static methods have to invoke \Drupal.
        if ($scopeFunction->isStatic()) {
            return [];
        }

        return [
            '\Drupal calls should be avoided in classes, use dependency injection instead'
        ];
    }
}
