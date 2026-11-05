<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests;

use PHPStan\Rules\Functions\CallToNonExistentFunctionRule;

final class DrushIntegrationTest extends DrupalRuleTestCase
{

    protected function getRule(): \PHPStan\Rules\Rule
    {
        // @phpstan-ignore phpstanApi.constructor
        return new CallToNonExistentFunctionRule(
            $this->createReflectionProvider(),
            true,
            true,
        );
    }

    public function testPaths(): void
    {
        if (\Drush\Drush::getMajorVersion() === '11') {
            self::markTestSkipped('No');
        }
        $this->analyse([
            __DIR__ . '/../fixtures/drupal/modules/drush_command/src/Commands/TestDrushCommands.php'
        ], []);
    }

    public function testSymfonyCompatibilityClasses(): void
    {
        $reflect = new \ReflectionClass(\Drush\Drush::class);
        $fileName = $reflect->getFileName();
        self::assertNotFalse($fileName);
        if (!is_dir(dirname($fileName, 2) . '/src-symfony-compatibility')) {
            self::markTestSkipped('Drush does not ship Symfony compatibility classes.');
        }
        self::assertTrue($this->createReflectionProvider()->hasClass('Drush\\Symfony\\LessStrictArgvInput'));
    }

}
