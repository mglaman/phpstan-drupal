<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use mglaman\PHPStanDrupal\Drupal\ConfigSchemaData;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use function count;

class ConfigGetDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

    public function __construct(
        private ConfigSchemaData $configSchemaData,
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
            if ($type !== null) {
                $types[] = $type;
            }
        }

        if (count($types) === 0) {
            return null;
        }

        if (count($types) === 1) {
            return $types[0];
        }

        return new UnionType($types);
    }

    private function resolveConfigName(MethodCall $methodCall, Scope $scope): ?string
    {
        $var = $methodCall->var;

        // The var should be a method call that returns Config/ImmutableConfig.
        // We need to find what config name was passed.
        if (!$var instanceof MethodCall && !$var instanceof StaticCall) {
            return null;
        }

        $methodName = $var instanceof MethodCall
            ? ($var->name instanceof Identifier ? $var->name->name : null)
            : ($var->name instanceof Identifier ? $var->name->name : null);

        if ($methodName === null) {
            return null;
        }

        // Pattern 1: \Drupal::config('name')
        if ($var instanceof StaticCall && $methodName === 'config') {
            return $this->extractFirstStringArg($var->getArgs());
        }

        // Pattern 2: $this->config('name') from ConfigFormBaseTrait
        if ($var instanceof MethodCall && $methodName === 'config') {
            return $this->extractFirstStringArg($var->getArgs());
        }

        // Pattern 3: $configFactory->get('name') — verify the receiver is ConfigFactoryInterface
        if ($var instanceof MethodCall && $methodName === 'get') {
            $receiverType = $scope->getType($var->var);
            $configFactoryType = new ObjectType(ConfigFactoryInterface::class);
            if ($configFactoryType->isSuperTypeOf($receiverType)->yes()) {
                return $this->extractFirstStringArg($var->getArgs());
            }
            return null;
        }

        // Pattern 4: $configFactory->getEditable('name') returns Config (mutable)
        if ($var instanceof MethodCall && $methodName === 'getEditable') {
            $receiverType = $scope->getType($var->var);
            $configFactoryType = new ObjectType(ConfigFactoryInterface::class);
            if ($configFactoryType->isSuperTypeOf($receiverType)->yes()) {
                return $this->extractFirstStringArg($var->getArgs());
            }
            return null;
        }

        return null;
    }

    /**
     * @param \PhpParser\Node\Arg[] $args
     */
    private function extractFirstStringArg(array $args): ?string
    {
        if (count($args) === 0) {
            return null;
        }

        $argValue = $args[0]->value;
        if ($argValue instanceof String_) {
            return $argValue->value;
        }

        return null;
    }
}
