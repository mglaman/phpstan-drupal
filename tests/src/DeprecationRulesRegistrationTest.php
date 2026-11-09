<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests;

use mglaman\PHPStanDrupal\Rules\Deprecations\ConfigEntityConfigExportRule;
use mglaman\PHPStanDrupal\Rules\Deprecations\GetDeprecatedServiceRule;
use mglaman\PHPStanDrupal\Rules\Deprecations\PluginAnnotationContextDefinitionsRule;
use mglaman\PHPStanDrupal\Rules\Deprecations\StaticServiceDeprecatedServiceRule;
use PHPStan\Testing\PHPStanTestCase;

final class DeprecationRulesRegistrationTest extends PHPStanTestCase
{
    use AdditionalConfigFilesTrait;
    use RegisteredRulesTrait;

    public function testDeprecationRulesRegistered(): void
    {
        $registeredRules = self::registeredRuleClasses();
        self::assertContains(StaticServiceDeprecatedServiceRule::class, $registeredRules);
        self::assertContains(GetDeprecatedServiceRule::class, $registeredRules);
        self::assertContains(PluginAnnotationContextDefinitionsRule::class, $registeredRules);
        self::assertContains(ConfigEntityConfigExportRule::class, $registeredRules);
    }
}
