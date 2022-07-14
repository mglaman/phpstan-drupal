<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Deprecations;

use mglaman\PHPStanDrupal\Internal\DeprecatedScopeCheck;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

final class SymfonyCmfRoutingInClassMethodSignatureRule implements Rule
{

    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof InClassMethodNode);
        if (DeprecatedScopeCheck::inDeprecatedScope($scope)) {
            return [];
        }
        [$major, $minor] = explode('.', \Drupal::VERSION, 3);
        if ($major !== '9' || (int) $minor < 1) {
            return [];
        }
        $method = $scope->getFunction();
        if (!$method instanceof MethodReflection) {
            throw new \PHPStan\ShouldNotHappenException();
        }

        $cmfRouteObjectInterfaceType = new ObjectType(\Symfony\Cmf\Component\Routing\RouteObjectInterface::class);
        $cmfRouteProviderInterfaceType = new ObjectType(\Symfony\Cmf\Component\Routing\RouteProviderInterface::class);
        $cmfLazyRouteCollectionType = new ObjectType(\Symfony\Cmf\Component\Routing\LazyRouteCollection::class);

        $methodSignature = ParametersAcceptorSelector::selectSingle($method->getVariants());

        $errors = [];
        $errorMessage = 'Parameter $%s of method %s() uses deprecated %s and removed in Drupal 10. Use %s instead.';
        foreach ($methodSignature->getParameters() as $parameter) {
            foreach ($parameter->getType()->getReferencedClasses() as $referencedClass) {
                $referencedClassType = new ObjectType($referencedClass);
                if ($cmfRouteObjectInterfaceType->equals($referencedClassType)) {
                    $errors[] = RuleErrorBuilder::message(
                        sprintf(
                            $errorMessage,
                            $parameter->getName(),
                            $method->getName(),
                            $referencedClass,
                            '\Drupal\Core\Routing\RouteObjectInterface'
                        )
                    )->tip('Change record: https://www.drupal.org/node/3151009')->build();
                } elseif ($cmfRouteProviderInterfaceType->equals($referencedClassType)) {
                    $errors[] = RuleErrorBuilder::message(
                        sprintf(
                            $errorMessage,
                            $parameter->getName(),
                            $method->getName(),
                            $referencedClass,
                            '\Drupal\Core\Routing\RouteProviderInterface'
                        )
                    )->tip('Change record: https://www.drupal.org/node/3151009')->build();
                } elseif ($cmfLazyRouteCollectionType->equals($referencedClassType)) {
                    $errors[] = RuleErrorBuilder::message(
                        sprintf(
                            $errorMessage,
                            $parameter->getName(),
                            $method->getName(),
                            $referencedClass,
                            '\Drupal\Core\Routing\LazyRouteCollection'
                        )
                    )->tip('Change record: https://www.drupal.org/node/3151009')->build();
                }
            }
        }

        $errorMessage = 'Return type of method %s::%s() has typehint with deprecated %s and is removed in Drupal 10. Use %s instead.';
        $returnClasses = $methodSignature->getReturnType()->getReferencedClasses();
        foreach ($returnClasses as $returnClass) {
            $returnType = new ObjectType($returnClass);
            if ($cmfRouteObjectInterfaceType->equals($returnType)) {
                $errors[] = RuleErrorBuilder::message(
                    sprintf(
                        $errorMessage,
                        $method->getDeclaringClass()->getName(),
                        $method->getName(),
                        $returnClass,
                        '\Drupal\Core\Routing\RouteObjectInterface'
                    )
                )->tip('Change record: https://www.drupal.org/node/3151009')->build();
            } elseif ($cmfRouteProviderInterfaceType->equals($returnType)) {
                $errors[] = RuleErrorBuilder::message(
                    sprintf(
                        $errorMessage,
                        $method->getDeclaringClass()->getName(),
                        $method->getName(),
                        $returnClass,
                        '\Drupal\Core\Routing\RouteProviderInterface'
                    )
                )->tip('Change record: https://www.drupal.org/node/3151009')->build();
            } elseif ($cmfLazyRouteCollectionType->equals($returnType)) {
                $errors[] = RuleErrorBuilder::message(
                    sprintf(
                        $errorMessage,
                        $method->getDeclaringClass()->getName(),
                        $method->getName(),
                        $returnClass,
                        '\Drupal\Core\Routing\LazyRouteCollection'
                    )
                )->tip('Change record: https://www.drupal.org/node/3151009')->build();
            }
        }
        return $errors;
    }
}
