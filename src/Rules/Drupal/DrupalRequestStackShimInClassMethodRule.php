<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use mglaman\PHPStanDrupal\Internal\DeprecatedScopeCheck;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

final class DrupalRequestStackShimInClassMethodRule implements Rule
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
        // Only valid for 9.3 -> 9.5. Deprecated in Drupal 10.
        if ($major !== '9' || (int) $minor < 3) {
            return [];
        }
        $method = $scope->getFunction();
        if (!$method instanceof MethodReflection) {
            throw new \PHPStan\ShouldNotHappenException();
        }
        $errors = [];

        $symfonyRequestStackType = new ObjectType(\Symfony\Component\HttpFoundation\RequestStack::class);

        $methodSignature = ParametersAcceptorSelector::selectSingle($method->getVariants());
        foreach ($methodSignature->getParameters() as $parameter) {
            foreach ($parameter->getType()->getReferencedClasses() as $referencedClass) {
                $referencedClassType = new ObjectType($referencedClass);
                if ($referencedClassType->equals($symfonyRequestStackType)) {
                    $errors[] = RuleErrorBuilder::message(
                        sprintf(
                            'Parameter $%s of method %s() uses \Symfony\Component\HttpFoundation\RequestStack. Use \Drupal\Core\Http\RequestStack instead for forward compatibility to Symfony 5.',
                            $parameter->getName(),
                            $method->getName(),
                        )
                    )
                        ->tip('Change record: https://www.drupal.org/node/3253744')
                        ->build();
                }
            }
        }
        $returnClasses = $methodSignature->getReturnType()->getReferencedClasses();
        foreach ($returnClasses as $returnClass) {
            $returnType = new ObjectType($returnClass);
            if ($returnType->equals($symfonyRequestStackType)) {
                $errors[] = RuleErrorBuilder::message('Return type of method %s::%s() uses \Symfony\Component\HttpFoundation\RequestStack. Use \Drupal\Core\Http\RequestStack instead for forward compatibility to Symfony 5.')
                    ->tip('Change record: https://www.drupal.org/node/3253744')
                    ->build();
            }
        }

        return $errors;
    }
}
