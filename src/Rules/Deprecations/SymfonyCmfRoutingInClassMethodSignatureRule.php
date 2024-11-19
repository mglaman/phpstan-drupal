<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Deprecations;

use Drupal;
use mglaman\PHPStanDrupal\Internal\DeprecatedScopeCheck;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use Symfony\Cmf\Component\Routing\LazyRouteCollection;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use function explode;
use function sprintf;

/**
 * @implements Rule<InClassMethodNode>
 */
final class SymfonyCmfRoutingInClassMethodSignatureRule implements Rule
{

    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (DeprecatedScopeCheck::inDeprecatedScope($scope)) {
            return [];
        }
        [$major, $minor] = explode('.', Drupal::VERSION, 3);
        if ($major !== '9' || (int) $minor < 1) {
            return [];
        }
        $method = $node->getMethodReflection();

        // The next lines are intentionally not using [at]phpstan-ignore [identifier].
        // The identifier would be 'class.notFound', which would not be true in
        // case of a D9 scan and thus would fail the 'phpstan analyze' phase.
        // @phpstan-ignore-next-line
        $cmfRouteObjectInterfaceType = new ObjectType(RouteObjectInterface::class);
        // @phpstan-ignore-next-line
        $cmfRouteProviderInterfaceType = new ObjectType(RouteProviderInterface::class);
        // @phpstan-ignore-next-line
        $cmfLazyRouteCollectionType = new ObjectType(LazyRouteCollection::class);

        $methodSignature = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            [],
            $method->getVariants()
        );

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
