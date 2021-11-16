<?php declare(strict_types=1);

namespace Drupal\phpstan_fixtures\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the ConfigEntityUsingDefaultStorage configuration entity.
 *
 * @ConfigEntityType(
 *   id = "config_entity_using_default_storage",
 *   label = @Translation("Config entity using default storage"),
 * )
 */
final class ConfigEntityUsingDefaultStorage extends ConfigEntityBase {

}
