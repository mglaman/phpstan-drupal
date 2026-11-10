<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use Drupal\Core\Extension\ModuleHandlerInterface;
use mglaman\PHPStanDrupal\Drupal\IncludeLoadResult;
use PhpParser\Node;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use function count;
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

        $include = $this->includeLoader->resolve($args[0], $args[1], $args[2] ?? null, $scope);
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
                    'File %s could not be loaded from %s::loadInclude because %s module is not found.',
                    $filename,
                    ModuleHandlerInterface::class,
                    $moduleName
                ))
                    ->identifier('loadIncludes.moduleNotFound')
                    ->build(),
            ],
            IncludeLoadResult::NotLoadable => [
                RuleErrorBuilder::message(sprintf(
                    'A file could not be loaded from %s::loadInclude',
                    ModuleHandlerInterface::class
                ))
                    ->identifier('loadIncludes.fileNotLoadable')
                    ->build(),
            ],
            IncludeLoadResult::FileNotFound => [
                RuleErrorBuilder::message(sprintf(
                    'File %s could not be loaded from %s::loadInclude',
                    $this->getModulePath($moduleName) . '/' . $filename,
                    ModuleHandlerInterface::class
                ))
                    ->identifier('loadIncludes.fileNotLoadable')
                    ->build(),
            ],
        };
    }
}
