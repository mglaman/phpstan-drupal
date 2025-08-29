<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Drupal\CacheableDependencyRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;

final class CachableDependencyRuleTest extends DrupalRuleTestCase {

    protected function getRule(): \PHPStan\Rules\Rule
    {
        return new CacheableDependencyRule();
    }

    /**
     * @dataProvider resultData
     *
     * @param list<array{0: string, 1: int, 2?: string|null}> $errorMessages
     */
    public function testRule(string $path, array $errorMessages): void
    {
        $this->analyse([$path], $errorMessages);
    }

    public static function resultData(): \Generator
    {
        yield [
            __DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/src/UsesIncorrectCacheableDependency.php',
            [
                [
                    'Calling addCacheableDependency($object) when $object does not implement CacheableDependencyInterface effectively disables caching and should be avoided.',
                    07
                ],
            ]
        ];

    }


}
