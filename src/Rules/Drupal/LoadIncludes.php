<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Arg;
use PhpParser\Node\Scalar\String_;
use Throwable;
use Drupal\Core\Extension\ModuleHandlerInterface;
use DrupalFinder\DrupalFinder;
use mglaman\PHPStanDrupal\Drupal\ExtensionDiscovery;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ObjectType;

class LoadIncludes implements Rule
{

    /**
     * The project root.
     */
    protected string $projectRoot;

    /**
     * LoadIncludes constructor.
     * @param string $project_root
     */
    public function __construct(string $project_root)
    {
        $this->projectRoot = $project_root;
    }

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof MethodCall);
        if (!$node->name instanceof Identifier) {
            return [];
        }
        $method_name = $node->name->toString();
        if ($method_name !== 'loadInclude') {
            return [];
        }
        $variable = $node->var;
        if (!$variable instanceof Variable) {
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
            // Try to invoke it similarily as the module handler itself.
            $finder = new DrupalFinder();
            $finder->locateRoot($this->projectRoot);
            $drupal_root = $finder->getDrupalRoot();
            $extensionDiscovery = new ExtensionDiscovery($drupal_root);
            $modules = $extensionDiscovery->scan('module');
            $module_arg = $node->args[0];
            assert($module_arg instanceof Arg);
            assert($module_arg->value instanceof String_);
            $type_arg = $node->args[1];
            assert($type_arg instanceof Arg);
            assert($type_arg->value instanceof String_);
            $name_arg = $node->args[2] ?? null;

            if ($name_arg === null) {
                $name_arg = $module_arg;
            }
            assert($name_arg instanceof Arg);
            assert($name_arg->value instanceof String_);

            $module_name = $module_arg->value->value;
            if (!isset($modules[$module_name])) {
                // @todo return error that module is missing.
                return [];
            }
            $type_prefix = $name_arg->value->value;
            $type_filename = $type_arg->value->value;
            $module = $modules[$module_name];
            $file = $drupal_root . '/' . $module->getPath() . "/$type_prefix.$type_filename";
            if (is_file($file)) {
                require_once $file;
                return [];
            }
            return [
                RuleErrorBuilder::message(sprintf(
                    'File %s could not be loaded from %s::loadInclude',
                    $file,
                    ModuleHandlerInterface::class
                ))
                    ->line($node->getLine())
                    ->build()
            ];
        } catch (Throwable $e) {
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
