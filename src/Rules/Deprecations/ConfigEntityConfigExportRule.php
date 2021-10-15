<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Deprecations;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\ShouldNotHappenException;

final class ConfigEntityConfigExportRule extends DeprecatedAnnotationsRuleBase
{

    protected function getExpectedInterface(): string
    {
        return 'Drupal\Core\Config\Entity\ConfigEntityInterface';
    }

    protected function doProcessNode(ClassReflection $reflection, Node\Stmt\Class_ $node, Scope $scope): array
    {
        $phpDoc = $reflection->getResolvedPhpDoc();
        // Plugins should always be annotated, but maybe this class is missing its
        // annotation since it swaps an existing one.
        if ($phpDoc === null || !$this->isAnnotated($phpDoc)) {
            return [];
        }
        $hasMatch = preg_match('/config_export\s?=\s?{/', $phpDoc->getPhpDocString());
        if ($hasMatch === false) {
            throw new ShouldNotHappenException('Unexpected error when trying to run match on phpDoc string.');
        }
        if ($hasMatch === 0) {
            return [
                'Configuration entity must define a `config_export` key. See https://www.drupal.org/node/2481909',
            ];
        }
        return [];
    }

    private function isAnnotated(ResolvedPhpDocBlock $phpDoc): bool
    {
        foreach ($phpDoc->getPhpDocNodes() as $docNode) {
            foreach ($docNode->children as $childNode) {
                if (($childNode instanceof PhpDocTagNode) && $childNode->name === '@ConfigEntityType') {
                    return true;
                }
            }
        }
        return false;
    }
}
