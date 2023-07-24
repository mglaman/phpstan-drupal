<?php declare(strict_types = 1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use Drupal\Core\Controller\ControllerBase;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

final class UnnecessaryDependencyInjectionRule implements Rule
{

    public function getNodeType(): string
    {
        return Node\Expr\MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof Node\Expr\MethodCall);

        // Check whether the controller extends the ControllerBase class.
        if (!$scope->isInClass()) {
            return [];
        }
        $classReflection = $scope->getClassReflection();
        $parentClasses = $classReflection->getParentClassesNames();
        if (!in_array(ControllerBase::class, $parentClasses, true)) {
            return [];
        }

        // Check whether the method is called in the create method.
        if ($scope->getFunctionName() !== 'create') {
            return [];
        }

        // Identify the service name.
        if (!$node->name instanceof Node\Identifier) {
            return [];
        }
        $method_name = $node->name->toString();
        if ($method_name !== 'get') {
            return [];
        }
        $methodReflection = $scope->getMethodReflection($scope->getType($node->var), $node->name->toString());
        if ($methodReflection === null) {
            return [];
        }
        $declaringClass = $methodReflection->getDeclaringClass();
        if ($declaringClass->getName() !== 'Symfony\Component\DependencyInjection\ContainerInterface') {
            return [];
        }

        $serviceNameArg = $node->args[0];
        assert($serviceNameArg instanceof Node\Arg);
        $serviceName = $serviceNameArg->value;
        if (!$serviceName instanceof Node\Scalar\String_) {
            return [];
        }
        $service = $serviceName->value;

        // Impose the rule for services contained in the controller base.
        $controllerBaseServices = [
            'entity_type.manager' => 'entityTypeManager()',
            'entity.form_builder' => 'entityFormBuilder()',
            'config.factory' => 'config($name)',
            'keyvalue' => 'keyValue($collection)',
            'state' => 'state()',
            'module_handler' => 'moduleHandler()',
            'form_builder' => 'formBuilder()',
            'current_user' => 'currentUser()',
            'language_manager' => 'languageManager()',
            'logger.factory' => 'getLogger($channel)',
            'messenger' => 'messenger()',
            'redirect.destination' => 'getRedirectDestination()',
            'string_translation' => 'getStringTranslation()',
            'cache.*' => 'cache($bin)',
        ];

        if (substr($service, 0, 6) === 'cache.' ||
            in_array($service, array_keys($controllerBaseServices), true)
        ) {
            return [
                RuleErrorBuilder::message(sprintf(
                    'The %s service is already defined in %s. Use the parent method %s instead.',
                    $service,
                    ControllerBase::class,
                    $controllerBaseServices[$service] ?? 'cache($bin)',
                ))
                ->line($node->getLine())
                ->build()
            ];
        }

        return [];
    }
}
