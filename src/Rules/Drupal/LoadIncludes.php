<?php declare(strict_types=1);

namespace PHPStan\Rules\Drupal;

use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Extension\ModuleHandlerInterface;
use DrupalFinder\DrupalFinder;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Drupal\ExtensionDiscovery;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\Rule;
use PHPStan\ShouldNotHappenException;

class LoadIncludes implements Rule
{

    /**
     * The project root.
     *
     * @var string
     */
    protected $projectRoot;

    /**
     * LoadIncludes constructor.
     */
    public function __construct($project_root)
    {
        $this->projectRoot = $project_root;
    }

    public function getNodeType(): string
    {
        return Node\Expr\MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof Node\Expr\MethodCall);

        try {
            $method_name = $node->name;
            if ($method_name instanceof Node\Identifier) {
                $method_name = $method_name->name;
            }
            if ($method_name !== 'loadInclude') {
                return [];
            }
            $var_name = $node->var->name;
            if ($var_name instanceof Node\Identifier) {
                $var_name = $var_name->name;
            }
            if (!$var_name) {
                return [];
            }
            $type = $scope->getVariableType($var_name);
            $reflected = new \ReflectionClass($type->getClassName());
            $implements = $reflected->implementsInterface(ModuleHandlerInterface::class);
            if (!$implements) {
                return [];
            }
            // Try to invoke it similarily as the module handler itself.
            $finder = new DrupalFinder();
            $finder->locateRoot($this->projectRoot);
            $drupal_root = $finder->getDrupalRoot();
            $extensionDiscovery = new ExtensionDiscovery($drupal_root);
            $modules = $extensionDiscovery->scan('module');
            $module_arg = $node->args[0]->value->value;
            $type_arg = $node->args[1]->value->value;
            $name_arg = !empty($node->args[2]) ? $node->args[2] : null;
            if (!$name_arg) {
                $name_arg = $module_arg;
            } else {
                $name_arg = $name_arg->value->value;
            }
            if (empty($modules[$module_arg])) {
                return [];
            }
            /** @var \PHPStan\Drupal\Extension $module */
            $module = $modules[$module_arg];
            $file = $drupal_root . '/' . $module->getPath() . "/$name_arg.$type_arg";
            if (is_file($file)) {
                require_once $file;
                return [];
            }
            return ['File could not be loaded from ModuleHandler::loadInclude'];
        } catch (\Throwable $e) {
        }

        return [];
    }
}
