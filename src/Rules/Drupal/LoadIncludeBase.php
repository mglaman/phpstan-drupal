<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use mglaman\PHPStanDrupal\Drupal\ExtensionMap;
use mglaman\PHPStanDrupal\Drupal\IncludeLoader;
use PhpParser\Node;
use PHPStan\Rules\Rule;

/**
 * @template TNodeType of Node
 * @implements Rule<TNodeType>
 */
abstract class LoadIncludeBase implements Rule
{

    public function __construct(
        protected readonly ExtensionMap $extensionMap,
        protected readonly IncludeLoader $includeLoader
    ) {
    }

    protected function getModulePath(string $moduleName): string
    {
        return $this->extensionMap->getModule($moduleName)?->getPath() ?? $moduleName;
    }
}
