<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Drupal;

use Drupal\Core\Config\ConfigFactoryInterface;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;
use function count;

/**
 * Provides helpers to resolve a Drupal config name from a method call chain.
 *
 * Used by ConfigGetUnknownKeyRule and ConfigGetDynamicReturnTypeExtension to
 * share the logic for extracting the config name from patterns such as:
 *   - \Drupal::config('name')->get(...)
 *   - $configFactory->get('name')->get(...)
 *   - $configFactory->getEditable('name')->get(...)
 *   - $this->config('name')->get(...)  (ConfigFormBaseTrait)
 */
trait ConfigNameResolverTrait
{

    private function resolveConfigName(MethodCall $methodCall, Scope $scope): ?string
    {
        $var = $methodCall->var;

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

        // Pattern 3: $configFactory->get('name')
        if ($var instanceof MethodCall && $methodName === 'get') {
            $receiverType = $scope->getType($var->var);
            $configFactoryType = new ObjectType(ConfigFactoryInterface::class);
            if ($configFactoryType->isSuperTypeOf($receiverType)->yes()) {
                return $this->extractFirstStringArg($var->getArgs());
            }
            return null;
        }

        // Pattern 4: $configFactory->getEditable('name')
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
