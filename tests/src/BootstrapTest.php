<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests;

use PHPStan\Testing\PHPStanTestCase;

final class BootstrapTest extends PHPStanTestCase
{
    /**
     * @var callable|null
     */
    private $previousErrorHandler;

    /**
     * @var array
     */
    private $gatheredWarnings = [];

    public function testContainerNotInitializedExceptionCatch(): void
    {
        $this->previousErrorHandler = set_error_handler([$this, 'handleError']);
        self::getContainer();
        restore_error_handler();

        self::assertNotEmpty($this->gatheredWarnings);
        $expectedWarnings = [
            'drupal/modules/contained_not_initialized/contained_not_initialized.install invoked the Drupal container outside of the scope of a function or class method. It was not loaded.',
            'drupal/modules/contained_not_initialized/contained_not_initialized.post_update.php invoked the Drupal container outside of the scope of a function or class method. It was not loaded.',
            'drupal/modules/contained_not_initialized/contained_not_initialized.views.inc invoked the Drupal container outside of the scope of a function or class method. It was not loaded.',
            'drupal/modules/contained_not_initialized/contained_not_initialized.views_execution.inc invoked the Drupal container outside of the scope of a function or class method. It was not loaded.',
            'drupal/modules/contained_not_initialized/contained_not_initialized.tokens.inc invoked the Drupal container outside of the scope of a function or class method. It was not loaded.',
            'drupal/modules/contained_not_initialized/contained_not_initialized.search_api.inc invoked the Drupal container outside of the scope of a function or class method. It was not loaded.',
            'drupal/modules/contained_not_initialized/contained_not_initialized.pathauto.inc invoked the Drupal container outside of the scope of a function or class method. It was not loaded.',
        ];
        self::assertEquals($expectedWarnings, $this->gatheredWarnings);
    }

    public function handleError(int $type, string $msg, string $file, int $line, array $context = []): bool
    {
        if (E_USER_WARNING !== $type) {
            $h = $this->previousErrorHandler;
            if (\is_callable($h)) {
                $val =  $h($type, $msg, $file, $line, $context);
                return $val ?? true;
            }
            return true;
        }
        $this->gatheredWarnings[] = $msg;
        return false;
    }

    public static function getAdditionalConfigFiles(): array
    {
        return array_merge(parent::getAdditionalConfigFiles(), [
            __DIR__ . '/../fixtures/config/phpunit-drupal-phpstan.neon',
        ]);
    }

}
