<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Drush\Commands;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeStorage;
use mglaman\PHPStanDrupal\Drush\Commands\PhpstanDrupalDrushCommands;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

final class PhpstanDrupalDrushCommandsTest extends TestCase
{

    public function testCreateNeon(): void
    {
        $container = new ContainerBuilder();

        $etmMock = $this->createStub(EntityTypeManagerInterface::class);

        $etdMock = $this->createStub(EntityTypeInterface::class);
        $etdMock->method('id')->willReturn('node');
        /** @phpstan-ignore-next-line */
        $etdMock->method('getClass')->willReturn(Node::class);
        /** @phpstan-ignore-next-line */
        $etdMock->method('getStorageClass')->willReturn(NodeStorage::class);

        $etmMock->method('getDefinitions')->willReturn([
            'node' => $etdMock
        ]);
        $container->set('entity_type.manager', $etmMock);
        \Drupal::setContainer($container);

        $command = new PhpstanDrupalDrushCommands();
        $command->setInput(new ArrayInput([]));
        $output = new BufferedOutput();
        $command->setOutput($output);

        $command->setup();
        self::assertEquals('parameters:
	level: 2
	paths:
		- web/modules/custom
		- web/themes/custom
		- web/profiles/custom
	drupal:
		entityMapping:
			node:
				class: Drupal\node\Entity\Node
				storage: Drupal\node\NodeStorage
', $output->fetch());
    }

}
