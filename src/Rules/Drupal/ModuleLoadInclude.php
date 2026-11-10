<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use mglaman\PHPStanDrupal\Drupal\IncludeLoadResult;
use PhpParser\Node;
use PhpParser\Node\Name;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use function count;
use function sprintf;

/**
 * Handles module_load_include dynamic file loading.
 *
 * @note may become deprecated and removed in D10
 * @see https://www.drupal.org/project/drupal/issues/697946
 *
 * @extends LoadIncludeBase<Node\Expr\FuncCall>
 */
final class ModuleLoadInclude extends LoadIncludeBase
{

    public function getNodeType(): string
    {
        return Node\Expr\FuncCall::class;
    }

    /**
     * @param Scope&DependencyTracker $scope
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Name) {
            return [];
        }
        $name = (string) $node->name;
        if ($name !== 'module_load_include') {
            return [];
        }
        $args = $node->getArgs();
        if (count($args) < 2) {
            return [];
        }

        $include = $this->includeLoader->resolve($args[1], $args[0], $args[2] ?? null, $scope);
        if ($include === null) {
            // Couldn't determine the module or file name, most probably
            // because it's a variable. Nothing to load.
            return [];
        }
        [$moduleName, $filename] = $include;

        return match ($this->includeLoader->load($moduleName, $filename, $scope)) {
            IncludeLoadResult::Loaded => [],
            IncludeLoadResult::ModuleNotFound => [
                RuleErrorBuilder::message(sprintf(
                    'File %s could not be loaded from module_load_include because %s module is not found.',
                    $filename,
                    $moduleName
                ))
                    ->identifier('moduleLoadInclude.moduleNotFound')
                    ->build(),
            ],
            IncludeLoadResult::NotLoadable => [
                RuleErrorBuilder::message('A file could not be loaded from module_load_include')
                    ->identifier('moduleLoadInclude.moduleNotLoadable')
                    ->build(),
            ],
            IncludeLoadResult::FileNotFound => [
                RuleErrorBuilder::message(sprintf(
                    'File %s could not be loaded from module_load_include.',
                    $this->getModulePath($moduleName) . '/' . $filename
                ))
                    ->identifier('moduleLoadInclude.moduleNotLoadable')
                    ->build(),
            ],
        };
    }
}
