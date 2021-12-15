<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use mglaman\PHPStanDrupal\Drupal\ExtensionMap;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Type\Constant\ConstantStringType;

abstract class LoadIncludeBase implements Rule
{

    /**
     * @var \mglaman\PHPStanDrupal\Drupal\ExtensionMap
     */
    protected $extensionMap;

    public function __construct(ExtensionMap $extensionMap)
    {
        $this->extensionMap = $extensionMap;
    }

    private function getStringArgValue(Node\Expr $expr, Scope $scope): ?string
    {
        $type = $scope->getType($expr);
        if ($type instanceof ConstantStringType) {
            return $type->getValue();
        }
        return null;
    }

    protected function parseLoadIncludeArgs(Node\Arg $module, Node\Arg $type, ?Node\Arg $name, Scope $scope): array
    {
        $moduleName = $this->getStringArgValue($module->value, $scope);
        if ($moduleName === null) {
            return [];
        }
        $fileType = $this->getStringArgValue($type->value, $scope);
        if ($fileType === null) {
            return [];
        }
        $baseName = null;
        if ($name !== null) {
            $baseName = $this->getStringArgValue($name->value, $scope);
        }
        if ($baseName === null) {
            $baseName = $moduleName;
        }

        return [$moduleName, "$baseName.$fileType"];
    }
}
