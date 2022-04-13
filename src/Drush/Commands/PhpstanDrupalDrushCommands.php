<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Drush\Commands;

use Drupal\Core\Serialization\Yaml;
use Drush\Commands\DrushCommands;

final class PhpstanDrupalDrushCommands extends DrushCommands
{

    /**
     * @command phpstan-drupal:setup
     * @option file
     * @bootstrap full
     */
    public function setup($options = ['file' => null]): void
    {
        $parameters = [
            'level' => 2,
            'paths' => [
                // @todo use drupal-finder for docroot
                'web/modules/custom',
                'web/themes/custom',
                'web/profiles/custom',
            ],
            'drupal' => [
                // @todo can we have this override _everything_ phpstan-drupal provides? or is it a merge.
                'entityMapping' => [
                ],
            ],

        ];

        $entity_type_manager = \Drupal::entityTypeManager();
        foreach ($entity_type_manager->getDefinitions() as $definition) {
            $parameters['drupal']['entityMapping'][$definition->id()] = [
                'class' => $definition->getClass(),
                'storage' => $definition->getStorageClass(),
            ];
        }

        $config = [
            'parameters' => $parameters,
        ];
        $output = Yaml::encode($config);
        // Replace 2 spaces with tabs for NEON compatibility.
        $output = str_replace('  ', "\t", $output);

        if ($options['file'] !== null) {
            file_put_contents($options['file'], $output);
        } else {
            $this->io()->writeln($output);
        }
    }

}
