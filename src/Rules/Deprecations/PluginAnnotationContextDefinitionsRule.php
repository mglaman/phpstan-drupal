<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Deprecations;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\ShouldNotHappenException;

final class PluginAnnotationContextDefinitionsRule extends DeprecatedAnnotationsRuleBase
{

    protected function getExpectedInterface(): string
    {
        return 'Drupal\Component\Plugin\ContextAwarePluginInterface';
    }

    protected function doProcessNode(ClassReflection $reflection, Node\Stmt\Class_ $node, Scope $scope): array
    {
        $annotation = $reflection->getResolvedPhpDoc();
        // Plugins should always be annotated, but maybe this class is missing its
        // annotation since it swaps an existing one.
        if ($annotation === null) {
            return [];
        }
        $hasMatch = preg_match('/context\s?=\s?{/', $annotation->getPhpDocString());
        if ($hasMatch === false) {
            throw new ShouldNotHappenException('Unexpected error when trying to run match on phpDoc string.');
        }
        if ($hasMatch === 1) {
            return [
                'Providing context definitions via the "context" key is deprecated in Drupal 8.7.x and will be removed before Drupal 9.0.0. Use the "context_definitions" key instead.',
            ];
        }
        return [];
    }
}
