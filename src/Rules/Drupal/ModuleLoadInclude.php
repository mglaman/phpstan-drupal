<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Handles module_load_include dynamic file loading.
 *
 * @note may become deprecated and removed in D10
 * @see https://www.drupal.org/project/drupal/issues/697946
 */
class ModuleLoadInclude extends LoadIncludeBase
{

    public function getNodeType(): string
    {
        return Node\Expr\FuncCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof Node\Expr\FuncCall);
        if (!$node->name instanceof \PhpParser\Node\Name) {
            return [];
        }
        $name = (string) $node->name;
        if ($name !== 'module_load_include') {
            return [];
        }
        $args = $node->getArgs();
        if (\count($args) < 2) {
            return [];
        }

        try {
            // Try to invoke it similarly as the module handler itself.
            [$moduleName, $filename] = $this->parseLoadIncludeArgs($args[1], $args[0], $args[2] ?? null, $scope);
            $module = $this->extensionMap->getModule($moduleName);
            if ($module === null) {
                return [
                    RuleErrorBuilder::message(sprintf(
                        'File %s could not be loaded from module_load_include because %s module is not found.',
                        $filename,
                        $moduleName
                    ))
                        ->line($node->getLine())
                        ->build()
                ];
            }
            $file = $module->getAbsolutePath() . DIRECTORY_SEPARATOR . $filename;
            if (is_file($file)) {
                require_once $file;
                return [];
            }
            return [
                RuleErrorBuilder::message(sprintf(
                    'File %s could not be loaded from module_load_include.',
                    $module->getPath() . '/' . $filename
                ))
                    ->line($node->getLine())
                    ->build()
            ];
        } catch (\Throwable $e) {
            return [
                RuleErrorBuilder::message('A file could not be loaded from module_load_include')
                    ->line($node->getLine())
                    ->build()
            ];
        }
    }
}
