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

        $autoloadFiles = $container->getParameter('autoload_files');
        $this->assertEquals([dirname(__DIR__, 2) . '/drupal-autoloader.php'], $autoloadFiles);
        if ($autoloadFiles !== null) {
            foreach ($autoloadFiles as $autoloadFile) {
                $autoloadFile = $fileHelper->normalizePath($autoloadFile);
                if (!is_file($autoloadFile)) {
                    $this->fail('Autoload file not found');
                }
                (static function (string $file) use ($container): void {
                    require_once $file;
                })($autoloadFile);
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
