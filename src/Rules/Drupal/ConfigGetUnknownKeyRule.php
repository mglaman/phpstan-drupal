<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use mglaman\PHPStanDrupal\Drupal\ConfigSchemaData;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use function count;
use function sprintf;

/**
 * Reports calls to Config::get() with a key that does not exist in the schema.
 *
 * Only fires for FullyValidatable config objects where the schema is complete,
 * guaranteeing that any key not listed is a genuine error.
 *
 * @implements Rule<Node\Expr\MethodCall>
 */
final class ConfigGetUnknownKeyRule implements Rule
{

    public function __construct(
        private ConfigSchemaData $configSchemaData,
    ) {
    }

    public function getNodeType(): string
    {
        return Node\Expr\MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Identifier || $node->name->name !== 'get') {
            return [];
        }

        // Verify the receiver is Config or a subclass (e.g. ImmutableConfig).
        $receiverType = $scope->getType($node->var);
        $configType = new ObjectType(Config::class);
        if (!$configType->isSuperTypeOf($receiverType)->yes()) {
            return [];
        }

        $args = $node->getArgs();
        if (count($args) !== 1) {
            return [];
        }

        $configName = $this->resolveConfigName($node, $scope);
        if ($configName === null) {
            return [];
        }

        if (!$this->configSchemaData->isFullyValidatable($configName)) {
            return [];
        }

        $keyType = $scope->getType($args[0]->value);
        $errors = [];
        foreach ($keyType->getConstantStrings() as $constantString) {
            $key = $constantString->getValue();
            if (!$this->configSchemaData->keyExistsInSchema($configName, $key)) {
                $errors[] = RuleErrorBuilder::message(
                    sprintf(
                        'Config key "%s" does not exist in the schema for "%s".',
                        $key,
                        $configName
                    )
                )
                    ->identifier('drupal.configGetUnknownKey')
                    ->build();
            }
        }

        return $errors;
    }

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
