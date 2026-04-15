<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Drupal\EntityListBuilderOperationsCacheabilityRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;

final class EntityListBuilderOperationsCacheabilityRuleTest extends DrupalRuleTestCase
{

    protected function getRule(): \PHPStan\Rules\Rule
    {
        return new EntityListBuilderOperationsCacheabilityRule();
    }

    public function testRule(): void
    {
        $this->analyse(
            [__DIR__ . '/data/entity-list-builder-operations-cacheability.php'],
            [
                [
                    'Method MissingCacheabilityGetOperations::getOperations() is missing the CacheableMetadata parameter added in Drupal 11.3. Update the signature to: getOperations(\Drupal\Core\Entity\EntityInterface $entity, ?\Drupal\Core\Cache\CacheableMetadata $cacheability = NULL).',
                    10,
                    'See https://www.drupal.org/node/3533080',
                ],
                [
                    'Method MissingCacheabilityGetDefaultOperations::getDefaultOperations() is missing the CacheableMetadata parameter added in Drupal 11.3. Update the signature to: getDefaultOperations(\Drupal\Core\Entity\EntityInterface $entity, ?\Drupal\Core\Cache\CacheableMetadata $cacheability = NULL).',
                    19,
                    'See https://www.drupal.org/node/3533080',
                ],
                [
                    'Method MissingCacheabilityBoth::getOperations() is missing the CacheableMetadata parameter added in Drupal 11.3. Update the signature to: getOperations(\Drupal\Core\Entity\EntityInterface $entity, ?\Drupal\Core\Cache\CacheableMetadata $cacheability = NULL).',
                    28,
                    'See https://www.drupal.org/node/3533080',
                ],
                [
                    'Method MissingCacheabilityBoth::getDefaultOperations() is missing the CacheableMetadata parameter added in Drupal 11.3. Update the signature to: getDefaultOperations(\Drupal\Core\Entity\EntityInterface $entity, ?\Drupal\Core\Cache\CacheableMetadata $cacheability = NULL).',
                    33,
                    'See https://www.drupal.org/node/3533080',
                ],
            ]
        );
    }

}
