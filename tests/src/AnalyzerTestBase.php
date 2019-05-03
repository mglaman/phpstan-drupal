<?php declare(strict_types=1);

namespace PHPStan\Drupal;

use Drupal\Core\DependencyInjection\ContainerNotInitializedException;
use PHPStan\Analyser\Analyser;
use PHPStan\DependencyInjection\ContainerFactory;
use PHPStan\File\FileHelper;
use PHPUnit\Framework\TestCase;

abstract class AnalyzerTestBase extends TestCase {

    protected function runAnalyze(string $path) {
        $rootDir = __DIR__ . '/../fixtures/drupal';
        $tmpDir = sys_get_temp_dir() . '/' . time() . 'phpstan';
        $containerFactory = new ContainerFactory($rootDir);
        $container = $containerFactory->create(
            $tmpDir,
            [__DIR__ . '/../fixtures/config/phpunit-drupal-phpstan.neon'],
            []
        );
        $fileHelper = $container->getByType(FileHelper::class);
        assert($fileHelper !== null);

        $bootstrapFile = $container->parameters['bootstrap'];
        $this->assertEquals(dirname(__DIR__, 2) . '/phpstan-bootstrap.php', $bootstrapFile);
        // Mock the autoloader.
        $GLOBALS['drupalVendorDir'] = dirname(__DIR__, 2) . '/vendor';
        if ($bootstrapFile !== null) {
            $bootstrapFile = $fileHelper->normalizePath($bootstrapFile);
            if (!is_file($bootstrapFile)) {
                $this->fail('Bootstrap file not found');
            }
            try {
                (static function (string $file): void {
                    require_once $file;
                })($bootstrapFile);
            } catch (ContainerNotInitializedException $e) {
                $trace = $e->getTrace();
                $offending_file = $trace[1];
                $this->fail(sprintf('%s called the Drupal container from unscoped code.', $offending_file['file']));
            }
            catch (\Throwable $e) {
                $this->fail('Could not load the bootstrap file');
            }
        }

        $analyser = $container->getByType(Analyser::class);
        assert($analyser !== null);

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
