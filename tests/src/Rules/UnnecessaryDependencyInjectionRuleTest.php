<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Drupal\UnnecessaryDependencyInjectionRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;

final class UnnecessaryDependencyInjectionRuleTest extends DrupalRuleTestCase
{

    protected function getRule(): \PHPStan\Rules\Rule
    {
        return new UnnecessaryDependencyInjectionRule();
    }

    /**
     * @dataProvider controllerData
     */
    public function testRule(string $path, array $errorMessages): void
    {
        // @phpstan-ignore-next-line
        $this->analyse([$path], $errorMessages);
    }

    public function controllerData(): \Generator
    {
        yield [
            __DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/src/Controller/UnnecessaryServiceInjectedController.php',
            [
                [
                    'The entity_type.manager service is already defined in Drupal\Core\Controller\ControllerBase. Use the parent method entityTypeManager() instead.',
                    34
                ],
                [
                    'The cache.default service is already defined in Drupal\Core\Controller\ControllerBase. Use the parent method cache($bin) instead.',
                    35
                ]
            ]
        ];
    }
}
