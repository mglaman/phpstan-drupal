<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use mglaman\PHPStanDrupal\Drupal\ExtensionListResultCacheValueExtension;
use PhpParser\Node;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Rules\Rule;

/**
 * Makes every analyzed file depend on the list of Drupal extensions.
 *
 * This rule never reports an error. A rule is the only place to declare a
 * dependency for each file, and a file whose error is a class that doesn't exist
 * has no other dependency to re-analyze it when a module provides that class.
 *
 * @implements Rule<FileNode>
 */
final class ExtensionListDependencyRule implements Rule
{

    public function getNodeType(): string
    {
        return FileNode::class;
    }

    /**
     * @param Scope&DependencyTracker $scope
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $scope->trackValueDependency(
            ExtensionListResultCacheValueExtension::class,
            ExtensionListResultCacheValueExtension::KEY
        );
        return [];
    }
}
