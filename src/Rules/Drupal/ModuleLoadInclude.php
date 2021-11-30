<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Arg;
use PhpParser\Node\Scalar\String_;
use Throwable;
use DrupalFinder\DrupalFinder;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use mglaman\PHPStanDrupal\Drupal\ExtensionDiscovery;
use PHPStan\Rules\Rule;

/**
 * Handles module_load_include dynamic file loading.
 *
 * @note may become deprecated and removed in D10
 * @see https://www.drupal.org/project/drupal/issues/697946
 */
class ModuleLoadInclude implements Rule
{

    /**
     * The project root.
     */
    protected string $projectRoot;

    /**
     * ModuleLoadInclude constructor.
     * @param string $project_root
     */
    public function __construct(string $project_root)
    {
        $this->projectRoot = $project_root;
    }

    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof FuncCall);
        if (!$node->name instanceof Name) {
            return [];
        }
        $name = (string) $node->name;
        if ($name !== 'module_load_include') {
            return [];
        }

        try {
            // Try to invoke it similarily as the module handler itself.
            $finder = new DrupalFinder();
            $finder->locateRoot($this->projectRoot);
            $drupal_root = $finder->getDrupalRoot();
            $extensionDiscovery = new ExtensionDiscovery($drupal_root);
            $modules = $extensionDiscovery->scan('module');
            $type_arg = $node->args[0];
            assert($type_arg instanceof Arg);
            assert($type_arg->value instanceof String_);
            $module_arg = $node->args[1];
            assert($module_arg instanceof Arg);
            assert($module_arg->value instanceof String_);
            $name_arg = $node->args[2] ?? null;

            if ($name_arg === null) {
                $name_arg = $module_arg;
            }
            assert($name_arg instanceof Arg);
            assert($name_arg->value instanceof String_);

            $module_name = $module_arg->value->value;
            if (!isset($modules[$module_name])) {
                // @todo return error that the module does not exist.
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
            return [sprintf('File %s could not be loaded from module_load_include', $file)];
        } catch (Throwable $e) {
            return ['A file could not be loaded from module_load_include'];
        }
    }
}
