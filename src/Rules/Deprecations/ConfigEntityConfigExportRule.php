<?php declare(strict_types=1);

namespace PHPStan\Rules\Deprecations;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;

final class ConfigEntityConfigExportRule extends DeprecatedAnnotationsRuleBase
{

    protected function getExpectedInterface(): string
    {
        return 'Drupal\Core\Config\Entity\ConfigEntityInterface';
    }

    protected function doProcessNode(ClassReflection $reflection, Node\Stmt\Class_ $node, Scope $scope): array
    {
        $annotation = $reflection->getResolvedPhpDoc();
        // Plugins should always be annotated, but maybe this class is missing its
        // annotation since it swaps an existing one.
        if ($annotation === null) {
            return [];
        }
        $hasMatch = strpos($annotation->getPhpDocString(), 'config_export = {') === false;
        if ($hasMatch) {
            return [
                'Configuration entity must define a `config_export` key. See https://www.drupal.org/node/2481909',
            ];
        }
        return [];
    }
}
