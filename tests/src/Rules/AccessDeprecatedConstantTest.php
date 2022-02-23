<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Deprecations\AccessDeprecatedConstant;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;

/**
 * @extends \mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase<\mglaman\PHPStanDrupal\Rules\Deprecations\AccessDeprecatedConstant>
 */
final class AccessDeprecatedConstantTest extends DrupalRuleTestCase
{

    protected function getRule(): \PHPStan\Rules\Rule
    {
        return new AccessDeprecatedConstant($this->createReflectionProvider());
    }

    public function testRule(): void
    {
        [$version] = explode('.', \Drupal::VERSION, 2);
        if ($version !== '9') {
            self::markTestSkipped('Only tested on Drupal 9.x.x');
        }

        $this->analyse(
            [__DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/phpstan_fixtures.module'],
            [
                [
                    'Call to deprecated constant SCHEMA_UNINSTALLED: Deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal\Core\Update\UpdateHookRegistry::SCHEMA_UNINSTALLED',
                    47
                ],
                [
                    'Call to deprecated constant FILE_INSECURE_EXTENSION_REGEX: Deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Use \Drupal\Core\File\FileSystemInterface::INSECURE_EXTENSION_REGEX.',
                    48
                ],
                [
                    'Call to deprecated constant PREG_CLASS_PUNCTUATION: Deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use \Drupal\search\SearchTextProcessorInterface::PREG_CLASS_PUNCTUATION',
                    49
                ],
            ],
        );
    }
}
