<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use Drupal;
use Drupal\Core\Hook\Attribute\Hook;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function class_exists;
use function count;
use function version_compare;

/**
 * Detects OOP hook implementations of hook_entity_operation and
 * hook_entity_operation_alter that are missing the CacheableMetadata
 * parameter added in Drupal 11.3.
 *
 * @implements Rule<ClassMethod>
 */
class HookEntityOperationCacheabilityRule implements Rule
{

    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!class_exists(Hook::class)) {
            return [];
        }

        if (version_compare(Drupal::VERSION, '11.3', '<') || version_compare(Drupal::VERSION, '12.0', '>=')) {
            return [];
        }

        if (!$scope->isInClass()) {
            return [];
        }

        $classReflection = $scope->getClassReflection();
        $nativeClass = $classReflection->getNativeReflection();
        $methodName = $node->name->toString();

        if (!$nativeClass->hasMethod($methodName)) {
            return [];
        }

        $nativeMethod = $nativeClass->getMethod($methodName);
        $hookAttributes = $nativeMethod->getAttributes(Hook::class);

        if (count($hookAttributes) === 0) {
            return [];
        }

        $errors = [];
        foreach ($hookAttributes as $hookAttribute) {
            /** @var Hook $hookInstance */
            $hookInstance = $hookAttribute->newInstance();

            if ($hookInstance->hook === 'entity_operation' && count($node->params) < 2) {
                $errors[] = RuleErrorBuilder::message(
                    sprintf(
                        'Method %s::%s() implements hook_entity_operation but is missing the CacheableMetadata parameter added in Drupal 11.3. Update the signature to include ?\Drupal\Core\Cache\CacheableMetadata $cacheability as the second parameter.',
                        $classReflection->getName(),
                        $methodName,
                    )
                )
                ->tip('See https://www.drupal.org/node/3533080')
                ->identifier('drupal.hookEntityOperationMissingCacheabilityParameter')
                ->build();
            }

            if ($hookInstance->hook === 'entity_operation_alter' && count($node->params) < 3) {
                $errors[] = RuleErrorBuilder::message(
                    sprintf(
                        'Method %s::%s() implements hook_entity_operation_alter but is missing the CacheableMetadata parameter added in Drupal 11.3. Update the signature to include ?\Drupal\Core\Cache\CacheableMetadata $cacheability as the third parameter.',
                        $classReflection->getName(),
                        $methodName,
                    )
                )
                ->tip('See https://www.drupal.org/node/3533080')
                ->identifier('drupal.hookEntityOperationAlterMissingCacheabilityParameter')
                ->build();
            }
        }

        return $errors;
    }
}
