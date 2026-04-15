<?php declare(strict_types=1);

namespace Drupal\phpstan_fixtures\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the ConfigTest configuration entity.
 *
 * @ConfigEntityType(
 *   id = "config_test",
 *   label = @Translation("Test configuration"),
 * )
 */
final class ConfigWithoutExport extends ConfigEntityBase {

}
