<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests;

use mglaman\PHPStanDrupal\Rules\Deprecations\ConfigEntityConfigExportRule;
use mglaman\PHPStanDrupal\Rules\Deprecations\GetDeprecatedServiceRule;
use mglaman\PHPStanDrupal\Rules\Deprecations\PluginAnnotationContextDefinitionsRule;
use mglaman\PHPStanDrupal\Rules\Deprecations\StaticServiceDeprecatedServiceRule;
use PHPStan\Testing\PHPStanTestCase;

final class DeprecationRulesNotInstalledTest extends PHPStanTestCase
{
    use RegisteredRulesTrait;

    public static function getAdditionalConfigFiles(): array
    {
        return array_merge(parent::getAdditionalConfigFiles(), [
            __DIR__ . '/../fixtures/config/phpunit-drupal-phpstan.neon',
            __DIR__ . '/../fixtures/config/phpunit-drupal-phpstan-no-deprecation-rules.neon',
        ]);
    }

    public function testDeprecationRulesNotRegistered(): void
    {
        $registeredRules = self::registeredRuleClasses();
        self::assertNotContains(StaticServiceDeprecatedServiceRule::class, $registeredRules);
        self::assertNotContains(GetDeprecatedServiceRule::class, $registeredRules);
        self::assertNotContains(PluginAnnotationContextDefinitionsRule::class, $registeredRules);
        self::assertContains(ConfigEntityConfigExportRule::class, $registeredRules);
    }
}
