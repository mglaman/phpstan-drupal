<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Drupal;

use Drupal\Core\DependencyInjection\ContainerNotInitializedException;
use PhpParser\Node\Arg;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use Throwable;
use function array_key_exists;
use function count;
use function is_file;
use const DIRECTORY_SEPARATOR;

/**
 * Loads module include files so the functions they declare are known.
 *
 * ModuleHandlerInterface::loadInclude() and module_load_include() load a
 * file at runtime. Analysis does the same, so calls to the functions in that
 * file resolve. The return type extension loads the file whenever the call is
 * analyzed. The rules report a missing module or file.
 *
 * Each file is loaded once, and the result is kept: PHP won't run a file
 * twice, so a later caller must get the same result as the first one.
 */
final class IncludeLoader
{

    /**
     * @var array<string, IncludeLoadResult>
     */
    private array $results = [];

    public function __construct(
        private readonly ExtensionMap $extensionMap
    ) {
    }

    /**
     * Resolves the module and file name of a loadInclude() style call.
     *
     * @return array{string, string}|null
     *   The module name and the file name, or NULL when either isn't a
     *   constant string.
     */
    public function resolve(Arg $module, Arg $type, ?Arg $name, Scope $scope): ?array
    {
        $moduleName = $this->getConstantString($module, $scope);
        $fileType = $this->getConstantString($type, $scope);
        if ($moduleName === null || $fileType === null) {
            return null;
        }
        $baseName = $moduleName;
        if ($name !== null) {
            $baseName = $this->getConstantString($name, $scope);
            if ($baseName === null) {
                return null;
            }
        }
        return [$moduleName, "$baseName.$fileType"];
    }

    /**
     * @param Scope&DependencyTracker $scope
     */
    public function load(string $moduleName, string $fileName, Scope $scope): IncludeLoadResult
    {
        $module = $this->extensionMap->getModule($moduleName);
        if ($module === null) {
            return IncludeLoadResult::ModuleNotFound;
        }
        $file = $module->getAbsolutePath() . DIRECTORY_SEPARATOR . $fileName;
        $scope->trackFileDependency($file);
        if (!array_key_exists($file, $this->results)) {
            $this->results[$file] = $this->require($file);
        }
        return $this->results[$file];
    }

    private function require(string $file): IncludeLoadResult
    {
        if (!is_file($file)) {
            return IncludeLoadResult::FileNotFound;
        }
        try {
            // Only the require itself is guarded: a broken include must not
            // crash the analysis, but PHPStan's own exceptions elsewhere must
            // propagate.
            require_once $file;
        } catch (ContainerNotInitializedException) {
            // Top-level code in the include needs a container, which does not
            // exist during analysis. Its top-level functions are still
            // declared.
            return IncludeLoadResult::Loaded;
        } catch (Throwable) {
            return IncludeLoadResult::NotLoadable;
        }
        return IncludeLoadResult::Loaded;
    }

    private function getConstantString(Arg $arg, Scope $scope): ?string
    {
        $strings = $scope->getType($arg->value)->getConstantStrings();
        return count($strings) > 0 ? $strings[0]->getValue() : null;
    }
}
