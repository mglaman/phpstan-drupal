<?php declare(strict_types=1);

namespace PHPStan\Drupal;

use Drupal\Core\DependencyInjection\ContainerNotInitializedException;
use PHPStan\DependencyInjection\ContainerFactory;
use PHPStan\File\FileHelper;
use PHPUnit\Framework\TestCase;

final class BootstrapTest extends TestCase
{
    private $previousErrorHandler;
    private $gatheredWarnings = [];

    public function testContainerNotInitializedExceptionCatch() {
        $this->previousErrorHandler = set_error_handler([$this, 'handleError']);
        $this->doDrupalBootstrap();
        restore_error_handler();

        $this->assertNotEmpty($this->gatheredWarnings);
        $expectedWarnings = [
            'drupal/modules/contained_not_initialized/contained_not_initialized.install invoked the Drupal container outside of the scope of a function or class method. It was not loaded.',
            'drupal/modules/contained_not_initialized/contained_not_initialized.post_update.php invoked the Drupal container outside of the scope of a function or class method. It was not loaded.',
            'drupal/modules/contained_not_initialized/contained_not_initialized.views.inc invoked the Drupal container outside of the scope of a function or class method. It was not loaded.',
            'drupal/modules/contained_not_initialized/contained_not_initialized.views_execution.inc invoked the Drupal container outside of the scope of a function or class method. It was not loaded.',
            'drupal/modules/contained_not_initialized/contained_not_initialized.tokens.inc invoked the Drupal container outside of the scope of a function or class method. It was not loaded.',
            'drupal/modules/contained_not_initialized/contained_not_initialized.search_api.inc invoked the Drupal container outside of the scope of a function or class method. It was not loaded.',
            'drupal/modules/contained_not_initialized/contained_not_initialized.pathauto.inc invoked the Drupal container outside of the scope of a function or class method. It was not loaded.',
        ];
        $this->assertEquals($expectedWarnings, $this->gatheredWarnings);
    }

    public function handleError($type, $msg, $file, $line, $context = array()): void
    {
        if (E_USER_WARNING !== $type) {
            $h = $this->previousErrorHandler;
            if (\is_callable($h)) {
                $h($type, $msg, $file, $line, $context);
            }
        } else {
            $this->gatheredWarnings[] = $msg;
        }
    }

    private function doDrupalBootstrap()
    {
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
    }

}
