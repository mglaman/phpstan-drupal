<?php declare(strict_types = 1);

namespace mglaman\PHPStanDrupal\Rules\Deprecations;

use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use Symfony\Component\DependencyInjection\ContainerInterface;
use PhpParser\Node\Arg;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use mglaman\PHPStanDrupal\Drupal\DrupalServiceDefinition;
use mglaman\PHPStanDrupal\Drupal\ServiceMap;
use PHPStan\Rules\Rule;

final class GetDeprecatedServiceRule implements Rule
{

    private ServiceMap $serviceMap;

    public function __construct(ServiceMap $serviceMap)
    {
        $this->serviceMap = $serviceMap;
    }

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof MethodCall);
        if (!$node->name instanceof Identifier) {
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
        if ($declaringClass->getName() !== ContainerInterface::class) {
            return [];
        }
        $serviceNameArg = $node->args[0];
        assert($serviceNameArg instanceof Arg);
        $serviceName = $serviceNameArg->value;
        // @todo check if var, otherwise throw.
        // ACTUALLY what if it was a constant? can we use a resolver.
        if (!$serviceName instanceof String_) {
            return [];
        }

        $service = $this->serviceMap->getService($serviceName->value);
        if (($service instanceof DrupalServiceDefinition) && $service->isDeprecated()) {
            return [$service->getDeprecatedDescription()];
        }

        return [];
    }
}
