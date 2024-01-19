<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use Drupal\Core\Extension\ModuleHandlerInterface;
use PhpParser\Node;
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
class LoadIncludes extends LoadIncludeBase
{

    public function getNodeType(): string
    {
        return Node\Expr\MethodCall::class;
    }

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
        if (!$type->isSuperTypeOf($moduleHandlerInterfaceType)->yes()) {
            return [];
        }

        try {
            // Try to invoke it similarly as the module handler itself.
            [$moduleName, $filename] = $this->parseLoadIncludeArgs($args[0], $args[1], $args[2] ?? null, $scope);
            if (!$moduleName && !$filename) {
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
                        ->line($node->getStartLine())
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
                    'File %s could not be loaded from %s::loadInclude',
                    $module->getPath() . '/' . $filename,
                    ModuleHandlerInterface::class
                ))
                    ->line($node->getStartLine())
                    ->build()
            ];
        } catch (Throwable $e) {
            return [
                RuleErrorBuilder::message(sprintf(
                    'A file could not be loaded from %s::loadInclude',
                    ModuleHandlerInterface::class
                ))
                    ->line($node->getStartLine())
                    ->build()
            ];
        }
    }
}
