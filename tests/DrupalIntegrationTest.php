<?php declare(strict_types=1);

namespace PHPStan\Drupal;

use PHPStan\Analyser\Analyser;
use PHPStan\DependencyInjection\ContainerFactory;
use PHPStan\File\FileHelper;
use PHPUnit\Framework\TestCase;

final class DrupalIntegrationTest extends TestCase {

    public function testInstallPhp() {
        $errors = $this->runAnalyze(__DIR__ . '/fixtures/drupal/core/install.php');
        $this->assertCount(0, $errors);
    }

    public function testDeprecatedUrlFunction() {
        $errors = $this->runAnalyze(__DIR__ . '/fixtures/drupal/modules/phpstan_fixtures/src/UsesDeprecatedUrlFunction.php');
        $this->assertCount(2, $errors);
        $error = array_shift($errors);
        $this->assertEquals('\Drupal calls should be avoided in classes, use dependency injection instead', $error->getMessage());
        $error = array_shift($errors);
        $this->assertEquals('Call to deprecated method url() of class Drupal.', $error->getMessage());
    }

    public function testDeprecatedImplements() {
        $errors = $this->runAnalyze(__DIR__ . '/fixtures/drupal/core/lib/Drupal/Core/Entity/EntityManager.php');
        $this->assertCount(1, $errors);
        $error = reset($errors);
        $this->assertEquals('Class Drupal\Core\Entity\EntityManager implements deprecated interface Drupal\Core\Entity\EntityManagerInterface.', $error->getMessage());
    }

    private function runAnalyze(string $path) {
        $rootDir = __DIR__ . '/fixtures/drupal';
        $containerFactory = new ContainerFactory($rootDir);
        $container = $containerFactory->create(
            sys_get_temp_dir() . '/' . time() . 'phpstan',
            [__DIR__ . '/fixtures/config/phpunit-drupal-phpstan.neon'],
            []
        );
        $fileHelper = $container->getByType(FileHelper::class);

        $bootstrapFile = $container->parameters['bootstrap'];
        $this->assertEquals(realpath(__DIR__ . '/../phpstan-bootstrap.php'), $bootstrapFile);
        // Mock the autoloader.
        $GLOBALS['drupalVendorDir'] = realpath(__DIR__) . '/../vendor';
        if ($bootstrapFile !== null) {
            $bootstrapFile = $fileHelper->normalizePath($bootstrapFile);
            if (!is_file($bootstrapFile)) {
                $this->fail('Bootstrap file not found');
            }
            try {
                (static function (string $file): void {
                    require_once $file;
                })($bootstrapFile);
            } catch (\Throwable $e) {
                $this->fail('Could not load the bootstrap file');
            }
        }

        $analyser = $container->getByType(Analyser::class);

        $file = $fileHelper->normalizePath($path);
        $errors = $analyser->analyse(
            [$file],
            false,
            null,
            null,
            true
        );
        foreach ($errors as $error) {
            $this->assertSame($fileHelper->normalizePath($file), $error->getFile());
        }
        return $errors;
    }

}
