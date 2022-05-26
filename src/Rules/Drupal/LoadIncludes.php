<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use Drupal\Core\Extension\ModuleHandlerInterface;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ObjectType;

class LoadIncludes extends LoadIncludeBase
{

    public function getNodeType(): string
    {
        return Node\Expr\MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof Node\Expr\MethodCall);
        if (!$node->name instanceof Node\Identifier) {
            return [];
        }
        $method_name = $node->name->toString();
        if ($method_name !== 'loadInclude') {
            return [];
        }
        $args = $node->getArgs();
        if (\count($args) < 2) {
            return [];
        }
        $variable = $node->var;
        if (!$variable instanceof Node\Expr\Variable) {
            return [];
        }
        $var_name = $variable->name;
        if (!is_string($var_name)) {
            throw new ShouldNotHappenException(sprintf('Expected string for variable in %s, please open an issue on GitHub https://github.com/mglaman/phpstan-drupal/issues', static::class));
        }
        $moduleHandlerInterfaceType = new ObjectType(ModuleHandlerInterface::class);
        $variableType = $scope->getVariableType($var_name);
        if (!$variableType->isSuperTypeOf($moduleHandlerInterfaceType)->yes()) {
            return [];
        }

        try {
            // Try to invoke it similarly as the module handler itself.
            [$moduleName, $filename] = $this->parseLoadIncludeArgs($args[0], $args[1], $args[2] ?? null, $scope);
            $module = $this->extensionMap->getModule($moduleName);
            if ($module === null) {
                return [
                    RuleErrorBuilder::message(sprintf(
                        'File %s could not be loaded from %s::loadInclude because %s module is not found.',
                        $filename,
                        ModuleHandlerInterface::class,
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
                    'File %s could not be loaded from %s::loadInclude',
                    $module->getPath() . '/' . $filename,
                    ModuleHandlerInterface::class
                ))
                    ->line($node->getLine())
                    ->build()
            ];
        } catch (\Throwable $e) {
            return [
                RuleErrorBuilder::message(sprintf(
                    'A file could not be loaded from %s::loadInclude',
                    ModuleHandlerInterface::class
                ))
                    ->line($node->getLine())
                    ->build()
            ];
        }
    }
}
