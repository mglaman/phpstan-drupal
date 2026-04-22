<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use Drupal;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityListBuilderInterface;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use function in_array;
use function version_compare;

/**
 * Detects EntityListBuilder subclasses overriding getOperations() or
 * getDefaultOperations() without the CacheableMetadata parameter added in
 * Drupal 11.3.
 *
 * The parameter was introduced as a deprecated workaround (via func_get_args)
 * in 11.3.0 and becomes a formal required-compatible parameter in 12.0.0.
 * This rule fires for Drupal 11.3.x only; for 12+ PHPStan's native
 * method-signature checking handles the incompatibility.
 *
 * @implements Rule<ClassMethod>
 */
class EntityListBuilderOperationsCacheabilityRule implements Rule
{

    private const METHODS = ['getOperations', 'getDefaultOperations'];

    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        // Version gate: only applies for Drupal 11.3.x.
        if (version_compare(Drupal::VERSION, '11.3', '<') || version_compare(Drupal::VERSION, '12.0', '>=')) {
            return [];
        }

        if (!$scope->isInClass()) {
            return [];
        }

        $methodName = $node->name->toString();
        if (!in_array($methodName, self::METHODS, true)) {
            return [];
        }

        $classReflection = $scope->getClassReflection();

        // Skip the base class itself — it uses func_get_args() intentionally.
        if ($classReflection->getName() === EntityListBuilder::class) {
            return [];
        }

        $classType = new ObjectType($classReflection->getName());

        // getDefaultOperations is protected and not part of the interface;
        // only flag it for subclasses of EntityListBuilder.
        $parentType = $methodName === 'getDefaultOperations'
            ? new ObjectType(EntityListBuilder::class)
            : new ObjectType(EntityListBuilderInterface::class);

        if (!$parentType->isSuperTypeOf($classType)->yes()) {
            return [];
        }

        // Second parameter (CacheableMetadata) must be present and correctly typed.
        $params = $node->params;
        if (isset($params[1]) && $this->isValidCacheabilityParam($params[1], $scope)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Method %s::%s() is missing the CacheableMetadata parameter added in Drupal 11.3. Update the signature to: %s(\Drupal\Core\Entity\EntityInterface $entity, ?\Drupal\Core\Cache\CacheableMetadata $cacheability = NULL).',
                    $classReflection->getName(),
                    $methodName,
                    $methodName,
                )
            )
            ->tip('See https://www.drupal.org/node/3533080')
            ->identifier('drupal.entityListBuilderMissingCacheabilityParameter')
            ->build(),
        ];
    }

    private function isValidCacheabilityParam(Node\Param $param, Scope $scope): bool
    {
        if ($param->type === null) {
            return false;
        }

        $type = $param->type;
        $isNullable = false;
        if ($type instanceof Node\NullableType) {
            $type = $type->type;
            $isNullable = true;
        }

        if ($type instanceof Node\Name) {
            $resolvedName = $scope->resolveName($type);
            if ($resolvedName !== 'Drupal\Core\Cache\CacheableMetadata') {
                return false;
            }
        } else {
            return false;
        }

        // It should be nullable, either via ? or by having a default value of NULL.
        return $isNullable || $this->isDefaultValueNull($param);
    }

    private function isDefaultValueNull(Node\Param $param): bool
    {
        if ($param->default instanceof Node\Expr\ConstFetch) {
            return $param->default->name->toLowerString() === 'null';
        }
        return false;
    }
}
