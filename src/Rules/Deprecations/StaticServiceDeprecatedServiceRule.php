<?php declare(strict_types = 1);

namespace mglaman\PHPStanDrupal\Rules\Deprecations;

use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use Drupal;
use PhpParser\Node\Arg;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use mglaman\PHPStanDrupal\Drupal\DrupalServiceDefinition;
use mglaman\PHPStanDrupal\Drupal\ServiceMap;
use PHPStan\Rules\Rule;

final class StaticServiceDeprecatedServiceRule implements Rule
{

    private ServiceMap $serviceMap;

    public function __construct(ServiceMap $serviceMap)
    {
        $this->serviceMap = $serviceMap;
    }

    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof StaticCall);
        if (!$node->name instanceof Identifier) {
            return [];
        }
        $method_name = $node->name->toString();
        if ($method_name !== 'service') {
            return [];
        }

        $class = $node->class;
        if ($class instanceof Name) {
            $calledOnType = $scope->resolveTypeByName($class);
        } else {
            $calledOnType = $scope->getType($class);
        }
        $methodReflection = $scope->getMethodReflection($calledOnType, $node->name->toString());

        if ($methodReflection === null) {
            return [];
        }
        $declaringClass = $methodReflection->getDeclaringClass();
        if ($declaringClass->getName() !== Drupal::class) {
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
