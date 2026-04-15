<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use Drupal;
use mglaman\PHPStanDrupal\Rules\Drupal\ProceduralHookEntityOperationCacheabilityRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use function version_compare;

final class ProceduralHookEntityOperationCacheabilityRuleTest extends DrupalRuleTestCase
{

    protected function getRule(): \PHPStan\Rules\Rule
    {
        return new ProceduralHookEntityOperationCacheabilityRule();
    }

    public function testRule(): void
    {
        $isApplicableVersion = version_compare(Drupal::VERSION, '11.3', '>=') && version_compare(Drupal::VERSION, '12.0', '<');
        $this->analyse(
            [__DIR__ . '/data/mymodule.module'],
            $isApplicableVersion ? [
                [
                    'Function mymodule_entity_operation() implements hook_entity_operation but is missing the CacheableMetadata parameter added in Drupal 11.3. Update the signature to: mymodule_entity_operation(\Drupal\Core\Entity\EntityInterface $entity, \Drupal\Core\Cache\CacheableMetadata $cacheability).',
                    7,
                    'See https://www.drupal.org/node/3533080',
                ],
                [
                    'Function mymodule_entity_operation_alter() implements hook_entity_operation_alter but is missing the CacheableMetadata parameter added in Drupal 11.3. Update the signature to: mymodule_entity_operation_alter(array &$operations, \Drupal\Core\Entity\EntityInterface $entity, \Drupal\Core\Cache\CacheableMetadata $cacheability).',
                    12,
                    'See https://www.drupal.org/node/3533080',
                ],
            ] : []
        );
    }

}
