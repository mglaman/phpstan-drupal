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
use PHPStan\Type\ObjectType;

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
     * @param string $project_root
     */
    public function __construct(string $project_root)
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
        if (!$node->name instanceof Node\Identifier) {
            return [];
        }
        $method_name = $node->name->toString();
        if ($method_name !== 'loadInclude') {
            return [];
        }
        $variable = $node->var;
        if (!$variable instanceof Node\Expr\Variable) {
            return [];
        }
        $var_name = $variable->name;
        if (!is_string($var_name)) {
            throw new ShouldNotHappenException(sprintf('Expected string for variable in %s, please open an issue on GitHub https://github.com/mglaman/phpstan-drupal/issues', get_called_class()));
        }
        $type = $scope->getVariableType($var_name);
        assert($type instanceof ObjectType);
        if (!class_exists($type->getClassName()) && !interface_exists($type->getClassName())) {
            throw new ShouldNotHappenException(sprintf('Could not find class for %s from reflection.', get_called_class()));
        }

        try {
            $reflected = new \ReflectionClass($type->getClassName());
            if (!$reflected->implementsInterface(ModuleHandlerInterface::class)) {
                return [];
            }
            // Try to invoke it similarily as the module handler itself.
            $finder = new DrupalFinder();
            $finder->locateRoot($this->projectRoot);
            $drupal_root = $finder->getDrupalRoot();
            $extensionDiscovery = new ExtensionDiscovery($drupal_root);
            $modules = $extensionDiscovery->scan('module');
            $module_arg = $node->args[0];
            assert($module_arg->value instanceof Node\Scalar\String_);
            $type_arg = $node->args[1];
            assert($type_arg->value instanceof Node\Scalar\String_);
            $name_arg = $node->args[2] ?? null;

            if ($name_arg === null) {
                $name_arg = $module_arg;
            }
            assert($name_arg->value instanceof Node\Scalar\String_);

            $module_name = $module_arg->value->value;
            if (!isset($modules[$module_name])) {
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
            return [sprintf('File %s could not be loaded from %s::loadInclude', $file, $type->getClassName())];
        } catch (\Throwable $e) {
            return [sprintf('A file could not be loaded from %s::loadInclude', $type->getClassName())];
        }
    }
}
