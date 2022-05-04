<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Drush\Commands;

use DrupalFinder\DrupalFinder;
use Drush\Commands\DrushCommands;

final class PhpstanDrupalDrushCommands extends DrushCommands
{

    /**
     * Creates configuration for PHPStan based on your Drupal site.
     *
     * @command phpstan:setup
     * @option drupal_root The path to Drupal.
     * @option file The output file.
     * @bootstrap full
     *
     * @phpstan-param array{drupal_root: string, file: string} $options
     */
    public function setup(array $options = ['drupal_root' => '', 'file' => '']): void
    {
        $finder = new DrupalFinder();

        if ($options['drupal_root'] === '') {
            $options['drupal_root'] = getcwd();
        }

        $finder->locateRoot($options['drupal_root']);
        if (!$finder->locateRoot($options['drupal_root'])) {
            throw new \RuntimeException('Unable to detect Drupal at ' . $options['drupal_root']);
        }

        $drupalRoot = str_replace($finder->getComposerRoot() . DIRECTORY_SEPARATOR, '', $finder->getDrupalRoot());

        $parameters = [
            'level' => 2,
            'paths' => [
                "$drupalRoot/modules/custom",
                "$drupalRoot/themes/custom",
                "$drupalRoot/profiles/custom",
            ],
            'drupal' => [
                'drupal_root' => $drupalRoot,
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
        $output = rtrim($this->createNeon($config));

        if ($options['file'] !== '') {
            file_put_contents($options['file'], $output);
        } else {
            $this->io()->writeln($output);
        }
    }

    private function createNeon(array $config, int $spacing = 0): string
    {
        $output = '';
        foreach ($config as $key => $value) {
            $indent = str_repeat("\t", $spacing);
            if (!is_array($value)) {
                $key = is_int($key) ? '- ' : "$key: ";
                if (!is_string($value)) {
                    $value = \json_encode($value);
                }

                $output .= "$indent$key" . $value . PHP_EOL;
            } elseif (count($value) === 0) {
                $output .= "$indent$key: []" . PHP_EOL;
            } else {
                $output .= "$indent$key:" . PHP_EOL;
                $output .= $this->createNeon($value, $spacing + 1);
            }
        }
        return $output;
    }
}
