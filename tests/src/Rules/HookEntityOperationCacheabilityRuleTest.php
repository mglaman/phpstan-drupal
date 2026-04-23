<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use Drupal;
use mglaman\PHPStanDrupal\Rules\Drupal\HookEntityOperationCacheabilityRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use function version_compare;

final class HookEntityOperationCacheabilityRuleTest extends DrupalRuleTestCase
{

    protected function getRule(): \PHPStan\Rules\Rule
    {
        return new HookEntityOperationCacheabilityRule();
    }

    public function testRule(): void
    {
        $isApplicableVersion = version_compare(Drupal::VERSION, '11.3', '>=') && version_compare(Drupal::VERSION, '12.0', '<');
        $this->analyse(
            [__DIR__ . '/data/hook-entity-operation-cacheability.php'],
            $isApplicableVersion ? [
                [
                    'Method BadEntityOperationHook::entityOperation() implements hook_entity_operation but is missing the CacheableMetadata parameter added in Drupal 11.3. Update the signature to include \Drupal\Core\Cache\CacheableMetadata $cacheability as the second parameter.',
                    10,
                    'See https://www.drupal.org/node/3533080',
                ],
                [
                    'Method BadEntityOperationAlterHookOneParam::entityOperationAlter() implements hook_entity_operation_alter but is missing the CacheableMetadata parameter added in Drupal 11.3. Update the signature to include \Drupal\Core\Cache\CacheableMetadata $cacheability as the third parameter.',
                    20,
                    'See https://www.drupal.org/node/3533080',
                ],
                [
                    'Method BadEntityOperationAlterHookTwoParams::entityOperationAlter() implements hook_entity_operation_alter but is missing the CacheableMetadata parameter added in Drupal 11.3. Update the signature to include \Drupal\Core\Cache\CacheableMetadata $cacheability as the third parameter.',
                    29,
                    'See https://www.drupal.org/node/3533080',
                ],
            ] : []
        );
    }

}
