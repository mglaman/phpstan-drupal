<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use Drupal;
use PhpParser\Node;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function basename;
use function count;
use function explode;
use function str_ends_with;
use function str_starts_with;
use function strlen;
use function substr_replace;
use function version_compare;

/**
 * Detects procedural implementations of hook_entity_operation and
 * hook_entity_operation_alter that are missing the CacheableMetadata
 * parameter added in Drupal 11.3.
 *
 * @implements Rule<Function_>
 */
class ProceduralHookEntityOperationCacheabilityRule implements Rule
{

    public function getNodeType(): string
    {
        return Function_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!str_ends_with($scope->getFile(), '.module') && !str_ends_with($scope->getFile(), '.inc')) {
            return [];
        }

        if (version_compare(Drupal::VERSION, '11.3', '<')) {
            return [];
        }

        $moduleName = explode('.', basename($scope->getFile()))[0];
        $functionName = $node->name->toString();

        if (!str_starts_with($functionName, "{$moduleName}_")) {
            return [];
        }

        $hookName = substr_replace($functionName, 'hook', 0, strlen($moduleName));

        if ($hookName === 'hook_entity_operation') {
            if (count($node->params) < 2 || !ParamHelper::isValidParam($node->params[1], 'Drupal\Core\Cache\CacheableMetadata', false, $scope)) {
                return [
                    RuleErrorBuilder::message(
                        sprintf(
                            'Function %s() implements hook_entity_operation but is missing the CacheableMetadata parameter added in Drupal 11.3. Update the signature to: %s(\Drupal\Core\Entity\EntityInterface $entity, \Drupal\Core\Cache\CacheableMetadata $cacheability).',
                            $functionName,
                            $functionName,
                        )
                    )
                    ->tip('See https://www.drupal.org/node/3533080')
                    ->identifier('drupal.proceduralHookEntityOperationMissingCacheabilityParameter')
                    ->build(),
                ];
            }
        }

        if ($hookName === 'hook_entity_operation_alter') {
            if (count($node->params) < 3 || !ParamHelper::isValidParam($node->params[2], 'Drupal\Core\Cache\CacheableMetadata', false, $scope)) {
                return [
                    RuleErrorBuilder::message(
                        sprintf(
                            'Function %s() implements hook_entity_operation_alter but is missing the CacheableMetadata parameter added in Drupal 11.3. Update the signature to: %s(array &$operations, \Drupal\Core\Entity\EntityInterface $entity, \Drupal\Core\Cache\CacheableMetadata $cacheability).',
                            $functionName,
                            $functionName,
                        )
                    )
                    ->tip('See https://www.drupal.org/node/3533080')
                    ->identifier('drupal.proceduralHookEntityOperationAlterMissingCacheabilityParameter')
                    ->build(),
                ];
            }
        }

        return [];
    }
}
