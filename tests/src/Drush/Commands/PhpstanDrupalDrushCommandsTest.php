<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Drush\Commands;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeStorage;
use mglaman\PHPStanDrupal\Drush\Commands\PhpstanDrupalDrushCommands;
use PHPStan\Testing\PHPStanTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

final class PhpstanDrupalDrushCommandsTest extends PHPStanTestCase
{
    private static string $tmpDir = '';

    protected function setUp(): void
    {
        parent::setUp();
        $tmpDir = \sys_get_temp_dir() . '/phpstan-drupal-tests';
        if (!@\mkdir($tmpDir, 0777) && !\is_dir($tmpDir)) {
            self::fail(\sprintf('Cannot create temp directory %s', $tmpDir));
        }
        self::$tmpDir = $tmpDir;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        @unlink(self::$tmpDir . '/phpstan-drupal.neon');
    }

    public function testCreateNeon(): void
    {
        $this->createDrupalContainer();

        $command = new PhpstanDrupalDrushCommands();
        $command->setInput(new ArrayInput([]));
        $output = new BufferedOutput();
        $command->setOutput($output);

        $command->setup();
        self::assertEquals('parameters:
	level: 2
	paths:
		- tests/fixtures/drupal/modules/custom
		- tests/fixtures/drupal/themes/custom
		- tests/fixtures/drupal/profiles/custom
	drupal:
		drupal_root: tests/fixtures/drupal
		entityMapping:
			node:
				class: Drupal\node\Entity\Node
				storage: Drupal\node\NodeStorage
', $output->fetch());
    }

    public function testWriteNeon(): void
    {
        $configFile = self::$tmpDir . '/phpstan-drupal.neon';

        $this->createDrupalContainer();

        $command = new PhpstanDrupalDrushCommands();
        $command->setInput(new ArrayInput([]));
        $output = new BufferedOutput();
        $command->setOutput($output);

        $command->setup([
            'drupal_root' => '',
            'file' => $configFile
        ]);

        self::assertFileExists($configFile);

        // Build the container, which validates all parameters.
        try {
            self::getContainer();
        } catch (\Throwable $e) {
            if (strpos(get_class($e), 'Nette\Schema\ValidationException') !== FALSE) {
                self::fail('PHPStan config validation error: ' . $e->getMessage());
            } else {
                throw $e;
            }
        }

        // Verify config after validation.
        self::assertEquals('parameters:
	level: 2
	paths:
		- tests/fixtures/drupal/modules/custom
		- tests/fixtures/drupal/themes/custom
		- tests/fixtures/drupal/profiles/custom
	drupal:
		drupal_root: tests/fixtures/drupal
		entityMapping:
			node:
				class: Drupal\node\Entity\Node
				storage: Drupal\node\NodeStorage', file_get_contents($configFile));
    }

    public static function getAdditionalConfigFiles(): array
    {
        return array_merge(parent::getAdditionalConfigFiles(), [
            __DIR__ . '/../../../fixtures/config/phpstan-drupal-includes.neon',
            self::$tmpDir . '/phpstan-drupal.neon',
        ]);
    }

    private function createDrupalContainer(): void
    {
        $container = new ContainerBuilder();

        $etdMock = $this->createStub(EntityTypeInterface::class);
        $etdMock->method('id')->willReturn('node');
        /** @phpstan-ignore-next-line */
        $etdMock->method('getClass')->willReturn(Node::class);
        /** @phpstan-ignore-next-line */
        $etdMock->method('getStorageClass')->willReturn(NodeStorage::class);

        $etmMock = $this->createStub(EntityTypeManagerInterface::class);
        $etmMock->method('getDefinitions')->willReturn([
            'node' => $etdMock
        ]);
        $container->set('entity_type.manager', $etmMock);
        \Drupal::setContainer($container);
    }
}
