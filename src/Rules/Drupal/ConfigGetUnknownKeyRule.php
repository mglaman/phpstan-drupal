<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use Drupal\Core\Config\Config;
use mglaman\PHPStanDrupal\Drupal\ConfigNameResolverTrait;
use mglaman\PHPStanDrupal\Drupal\ConfigSchemaData;
use PhpParser\Node;
use PhpParser\Node\Identifier;
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
    use ConfigNameResolverTrait;

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
}
