<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name\FullyQualified;
use Drupal;
use PHPUnit\Framework\Test;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\Render\Element\ElementInterface;
use Drupal\Core\Render\Element\FormElementInterface;
use Drupal\Core\Entity\EntityInterface;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\Rule;
use PHPStan\ShouldNotHappenException;

class GlobalDrupalDependencyInjectionRule implements Rule
{
    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof StaticCall);

        // Only check static calls to \Drupal
        if (!($node->class instanceof FullyQualified) || (string) $node->class !== Drupal::class) {
            return [];
        }
        // Do not raise if called inside of a trait.
        if (!$scope->isInClass() || $scope->isInTrait()) {
            return [];
        }
        $scopeClassReflection = $scope->getClassReflection();
        if ($scopeClassReflection === null) {
            throw new ShouldNotHappenException();
        }

        $classReflection = $scopeClassReflection->getNativeReflection();
        $allowed_list = [
            // Ignore tests.
            Test::class,
            // Typed data objects cannot use dependency injection.
            TypedDataInterface::class,
            // Render elements cannot use dependency injection.
            ElementInterface::class,
            FormElementInterface::class,
            'Drupal\config_translation\FormElement\ElementInterface',
            // Entities don't use services for now
            // @see https://www.drupal.org/project/drupal/issues/2913224
            EntityInterface::class,
        ];
        $implemented_interfaces = $classReflection->getInterfaceNames();

        foreach ($allowed_list as $item) {
            if (in_array($item, $implemented_interfaces, true)) {
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
