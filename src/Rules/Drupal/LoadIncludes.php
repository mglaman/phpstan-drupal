<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use Drupal\Core\DependencyInjection\ContainerNotInitializedException;
use Drupal\Core\Extension\ModuleHandlerInterface;
use PhpParser\Node;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use Throwable;
use function count;
use function is_file;
use function sprintf;

/**
 * @extends LoadIncludeBase<Node\Expr\MethodCall>
 */
final class LoadIncludes extends LoadIncludeBase
{

    public function getNodeType(): string
    {
        return Node\Expr\MethodCall::class;
    }

    /**
     * @param Scope&DependencyTracker $scope
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Node\Identifier) {
            return [];
        }
        $method_name = $node->name->toString();
        if ($method_name !== 'loadInclude') {
            return [];
        }
        $args = $node->getArgs();
        if (count($args) < 2) {
            return [];
        }
        $type = $scope->getType($node->var);
        $moduleHandlerInterfaceType = new ObjectType(ModuleHandlerInterface::class);
        if (!$moduleHandlerInterfaceType->isSuperTypeOf($type)->yes()) {
            return [];
        }

        // Try to invoke it similarly as the module handler itself.
        [$moduleName, $filename] = $this->parseLoadIncludeArgs($args[0], $args[1], $args[2] ?? null, $scope);
        if ($moduleName === false || $filename === false) {
            // Couldn't determine module- nor file-name, most probably
            // because it's a variable. Nothing to load, bail now.
            return [];
        }
        $module = $this->extensionMap->getModule($moduleName);
        if ($module === null) {
            return [
                RuleErrorBuilder::message(sprintf(
                    'File %s could not be loaded from %s::loadInclude because %s module is not found.',
                    $filename,
                    ModuleHandlerInterface::class,
                    $moduleName
                ))
                ->identifier('loadIncludes.moduleNotFound')
                ->build()
            ];
        }

        $file = $module->getAbsolutePath() . DIRECTORY_SEPARATOR . $filename;
        $scope->trackFileDependency($file);
        if (is_file($file)) {
            try {
                // Load the include so its symbols are available for the rest
                // of the analysis, mirroring what loadInclude() does at
                // runtime. Only the require itself is guarded: a broken
                // include must not crash the analysis, but PHPStan's own
                // exceptions elsewhere must propagate.
                require_once $file;
            } catch (ContainerNotInitializedException $e) {
                // Top-level code in the include needs a container, which does
                // not exist during analysis. Its top-level functions are still
                // declared. Reporting this would also be unreliable: PHP won't
                // re-run the file, so only the first call in a process throws.
                return [];
            } catch (Throwable $e) {
                return [
                    RuleErrorBuilder::message(sprintf(
                        'A file could not be loaded from %s::loadInclude',
                        ModuleHandlerInterface::class
                    ))
                    ->identifier('loadIncludes.fileNotLoadable')
                    ->build()
                ];
            }
            return [];
        }
        return [
            RuleErrorBuilder::message(sprintf(
                'File %s could not be loaded from %s::loadInclude',
                $module->getPath() . '/' . $filename,
                ModuleHandlerInterface::class
            ))
            ->identifier('loadIncludes.fileNotLoadable')
            ->build()
        ];
    }
}
