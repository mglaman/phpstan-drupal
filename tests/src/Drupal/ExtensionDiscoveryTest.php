<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Drupal;

use mglaman\PHPStanDrupal\Drupal\Extension;
use mglaman\PHPStanDrupal\Drupal\ExtensionDiscovery;
use PHPUnit\Framework\TestCase;

final class ExtensionDiscoveryTest extends TestCase
{

    /**
     * Extensions under an unknown profiles directory must still sort.
     *
     * scan() filters these out before sorting, but sort() is protected and
     * must not leave holes in the arrays it hands to array_multisort().
     */
    public function testSortHandlesExtensionsUnderUnknownProfileDirectory(): void
    {
        $root = __DIR__ . '/../../fixtures/drupal';
        $discovery = new class($root) extends ExtensionDiscovery {
            /**
             * @param \mglaman\PHPStanDrupal\Drupal\Extension[] $files
             * @param array<string, int> $weights
             *
             * @return \mglaman\PHPStanDrupal\Drupal\Extension[]
             */
            public function sortForTest(array $files, array $weights): array
            {
                return $this->sort($files, $weights);
            }
        };
        $discovery->setProfileDirectories(['core/profiles/standard']);

        $core = new Extension($root, 'module', 'core/modules/node/node.info.yml');
        $core->subpath = 'modules/node';
        $core->origin = 'core';

        $orphan = new Extension($root, 'module', 'profiles/not_a_profile/modules/orphan/orphan.info.yml');
        $orphan->subpath = 'profiles/not_a_profile/modules/orphan';
        $orphan->origin = '';

        $custom = new Extension($root, 'module', 'modules/custom/custom.info.yml');
        $custom->subpath = 'modules/custom';
        $custom->origin = '';

        $sorted = $discovery->sortForTest(
            ['custom' => $custom, 'orphan' => $orphan, 'node' => $core],
            ['core' => 0, '' => 3]
        );

        self::assertSame(['node', 'orphan', 'custom'], array_keys($sorted));
    }
}
