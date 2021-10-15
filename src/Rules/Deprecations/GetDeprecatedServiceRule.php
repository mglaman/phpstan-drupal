<?php declare(strict_types = 1);

namespace mglaman\PHPStanDrupal\Rules\Deprecations;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use mglaman\PHPStanDrupal\Drupal\DrupalServiceDefinition;
use mglaman\PHPStanDrupal\Drupal\ServiceMap;
use PHPStan\Rules\Rule;

final class GetDeprecatedServiceRule implements Rule
{

    /**
     * @var ServiceMap
     */
    private $serviceMap;

    public function __construct(ServiceMap $serviceMap)
    {
        $this->serviceMap = $serviceMap;
    }

    public function getNodeType(): string
    {
        return Node\Expr\MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof Node\Expr\MethodCall);
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
        // @todo check if var, otherwise throw.
        // ACTUALLY what if it was a constant? can we use a resolver.
        if (!$serviceName instanceof Node\Scalar\String_) {
            return [];
        }

        $service = $this->serviceMap->getService($serviceName->value);
        if (($service instanceof DrupalServiceDefinition) && $service->isDeprecated()) {
            return [$service->getDeprecatedDescription()];
        }

        return [];
    }
}
