<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type;

use Drupal\Core\Config\Config;
use mglaman\PHPStanDrupal\Drupal\ConfigNameResolverTrait;
use mglaman\PHPStanDrupal\Drupal\ConfigSchemaData;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;

class ConfigGetDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    use ConfigNameResolverTrait;

    public function __construct(
        private ConfigSchemaData $configSchemaData,
        private bool $configGetReturnType = false,
    ) {
    }

    public function getClass(): string
    {
        return Config::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'get';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): ?Type {
        if (!$this->configGetReturnType) {
            return null;
        }

        $args = $methodCall->getArgs();
        if (count($args) !== 1) {
            return null;
        }

        $configName = $this->resolveConfigName($methodCall, $scope);
        if ($configName === null) {
            return null;
        }

        $keyType = $scope->getType($args[0]->value);
        $constantStrings = $keyType->getConstantStrings();
        if (count($constantStrings) === 0) {
            return null;
        }

        $types = [];
        foreach ($constantStrings as $constantString) {
            $key = $constantString->getValue();
            $type = $this->configSchemaData->getTypeForKey($configName, $key);
            if ($type === null) {
                // One unresolvable key poisons the union: narrowing to only
                // the resolved branches would drop the mixed branch.
                return null;
            }
            $types[] = $type;
        }

        return TypeCombinator::union(...$types);
    }
}
