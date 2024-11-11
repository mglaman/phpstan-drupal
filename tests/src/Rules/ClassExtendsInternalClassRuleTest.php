<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Classes\ClassExtendsInternalClassRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Rule;

final class ClassExtendsInternalClassRuleTest extends DrupalRuleTestCase
{
    private const DRUPAL_CORE_STUBS_MAIN_DIRECTORY = __DIR__ . '/../../fixtures/drupal/core/lib/Drupal/Core/PHPStanDrupalTests';

    protected function getRule(): Rule
    {
        return new ClassExtendsInternalClassRule($this->createReflectionProvider());
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::cleanDrupalCoreStubs();
        foreach (self::getDrupalCoreStubs() as $filepath => $content) {
            $directory = \dirname($filepath);
            if (!is_dir($directory)) {
                \mkdir($directory, 0777, true);
            }
            \file_put_contents($filepath, $content);
        }
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        self::cleanDrupalCoreStubs();
    }

    /**
     * @dataProvider pluginData
     *
     * @param list<array{0: string, 1: int, 2?: string|null}> $errorMessages
     */
    public function testRule(string $path, array $errorMessages): void
    {
        [$version, $minor] = explode('.', \Drupal::VERSION, 3);
        if (($version >= '10' || ($version === '9' && (int) $minor >= 4))
            && $path === __DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/src/Form/ExtendsContentEntityDeleteForm.php') {
                self::markTestSkipped('@internal was removed in 10.0.x and 9.4.x');
        }
        $this->analyse([$path], $errorMessages);
    }

    public static function pluginData(): \Generator
    {
        yield 'extends an internal class from a non-shared namespace: phpstan_fixtures extends an internal class from Drupal core.' => [
            __DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/src/Internal/ExtendsDrupalCoreInternalClass.php',
            [
                [
                    'Class Drupal\phpstan_fixtures\Internal\ExtendsDrupalCoreInternalClass extends @internal class Drupal\Core\InternalClass.',
                    7,
                    'Read the Drupal core backwards compatibility and internal API policy: https://www.drupal.org/about/core/policies/core-change-policies/drupal-8-and-9-backwards-compatibility-and-internal-api#internal',
                ],
            ],
        ];
        yield 'extends an internal class from a non-shared namespace: phpstan_fixtures extends an internal class from Drupal core with a deeper namespace.' => [
            __DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/src/Internal/ExtendsDrupalCorePHPStanDrupalTestsInternalClass.php',
            [
                [
                    'Class Drupal\phpstan_fixtures\Internal\ExtendsDrupalCorePHPStanDrupalTestsInternalClass extends @internal class Drupal\Core\PHPStanDrupalTests\InternalClass.',
                    7,
                    'Read the Drupal core backwards compatibility and internal API policy: https://www.drupal.org/about/core/policies/core-change-policies/drupal-8-and-9-backwards-compatibility-and-internal-api#internal',
                ],
            ],
        ];
        yield 'extends an internal class from a non-shared namespace: phpstan_fixtures extends an internal class from module module_with_internal_classes.' => [
            __DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/src/Internal/ExtendsPHPStanDrupalModuleWithInternalClassesInternalClass.php',
            [
                [
                    'Class Drupal\phpstan_fixtures\Internal\ExtendsPHPStanDrupalModuleWithInternalClassesInternalClass extends @internal class Drupal\module_with_internal_classes\Foo\InternalClass.',
                    7
                ],
            ],
        ];
        yield 'extends an internal class from a non-shared namespace: anonymous class extends from an internal class from module module_with_internal_classes.' => [
            __DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/src/Internal/WithAnAnonymousClassExtendingAnInternalClass.php',
            [
                [
                    'Anonymous class extends @internal class Drupal\module_with_internal_classes\Foo\InternalClass.',
                    13
                ],
            ],
        ];
        yield 'extends an internal class from a non-shared namespace: Drupal Core extends an internal class from module phpstan_fixtures.' => [
            __DIR__ . '/../../fixtures/drupal/core/lib/Drupal/Core/PHPStanDrupalTests/ExtendsPhpStanFixturesInternalClass.php',
            [
                [
                    'Class Drupal\Core\PHPStanDrupalTests\ExtendsPhpStanFixturesInternalClass extends @internal class Drupal\phpstan_fixtures\InternalClass.',
                    7
                ],
            ],
        ];
        yield 'extends an internal class from a shared namespace: Drupal Core extends from an internal class from itself.' => [
            __DIR__ . '/../../fixtures/drupal/core/lib/Drupal/Core/PHPStanDrupalTests/ExtendsRootInternalClass.php',
            [],
        ];
        yield 'extends an internal class from a shared namespace: Drupal Core extends from an internal class from itself with a deeper namespace.' => [
            __DIR__ . '/../../fixtures/drupal/core/lib/Drupal/Core/PHPStanDrupalTests/ExtendsInternalClass.php',
            [],
        ];
        yield 'extends an internal class from a shared namespace: phpstan_fixtures extends from an internal class from itself.' => [
            __DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/src/Internal/ExtendsRootInternalClass.php',
            [],
        ];
        yield 'extends an internal class from a shared namespace: phpstan_fixtures extends from an internal class from itself with a deeper namespace.' => [
            __DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/src/Internal/ExtendsInternalClass.php',
            [],
        ];
        yield 'does not extend an internal class: does not extends any class.' => [
            __DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/src/Internal/DoesNotExtendsAnyClass.php',
            [],
        ];
        yield 'does not extend an internal class: phpstan_fixtures extends an external class from Drupal Core.' => [
            __DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/src/Internal/ExtendsDrupalCoreExternalClass.php',
            [],
        ];
        yield 'does not extend an internal class: phpstan_fixtures extends an external class from module module_with_internal_classes.' => [
            __DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/src/Internal/ExtendsPHPStanDrupalModuleWithInternalClassesExternalClass.php',
            [],
        ];
        yield 'tip for ContentEntityDeleteForm' => [
            __DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/src/Form/ExtendsContentEntityDeleteForm.php',
            [
                [
                    'Class Drupal\phpstan_fixtures\Form\ExtendsContentEntityDeleteForm extends @internal class Drupal\Core\Entity\ContentEntityDeleteForm.',
                    7,
                    'Extend \Drupal\Core\Entity\ContentEntityConfirmFormBase. See https://www.drupal.org/node/2491057'
                ]
            ],
        ];
    }

    private static function cleanDrupalCoreStubs(): void {
        foreach (\array_keys(\iterator_to_array(self::getDrupalCoreStubs())) as $filepath) {
            if (!is_file($filepath)) {
                continue;
            }
            \unlink($filepath);
        }

        if (is_dir(self::DRUPAL_CORE_STUBS_MAIN_DIRECTORY)) {
            \rmdir(self::DRUPAL_CORE_STUBS_MAIN_DIRECTORY);
        }
    }

    private static function getDrupalCoreStubs(): \Generator {
        yield __DIR__ . '/../../fixtures/drupal/core/lib/Drupal/Core/InternalClass.php' => <<<'CODE'
<?php

namespace Drupal\Core;

/**
 * @internal
 */
class InternalClass {}
CODE;

        yield self::DRUPAL_CORE_STUBS_MAIN_DIRECTORY . '/InternalClass.php' => <<<'CODE'
<?php

namespace Drupal\Core\PHPStanDrupalTests;

/**
 * @internal
 */
class InternalClass {}
CODE;

        yield self::DRUPAL_CORE_STUBS_MAIN_DIRECTORY . '/ExtendsRootInternalClass.php' => <<<'CODE'
<?php

namespace Drupal\Core\PHPStanDrupalTests;

use Drupal\Core\InternalClass;

class ExtendsRootInternalClass extends InternalClass {}
CODE;

        yield self::DRUPAL_CORE_STUBS_MAIN_DIRECTORY . '/ExtendsInternalClass.php' => <<<'CODE'
<?php

namespace Drupal\Core\PHPStanDrupalTests;

class ExtendsInternalClass extends InternalClass {}
CODE;
        yield self::DRUPAL_CORE_STUBS_MAIN_DIRECTORY . '/ExtendsPhpStanFixturesInternalClass.php' => <<<'CODE'
<?php

namespace Drupal\Core\PHPStanDrupalTests;

use Drupal\phpstan_fixtures\InternalClass;

class ExtendsPhpStanFixturesInternalClass extends InternalClass {}
CODE;
        yield self::DRUPAL_CORE_STUBS_MAIN_DIRECTORY . '/ExternalClass.php' => <<<'CODE'
<?php

namespace Drupal\Core\PHPStanDrupalTests;

class ExternalClass {}
CODE;
    }
}
