<?php declare(strict_types=1);

namespace Drupal\phpstan_fixtures\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the ConfigEntityUsingCustomStorage configuration entity.
 *
 * @ConfigEntityType(
 *   id = "config_entity_using_custom_storage",
 *   label = @Translation("Config entity using custom storage"),
 *   handlers = {
 *     "storage" = "Drupal\phpstan_fixtures\CustomConfigEntityStorage",
 *   }
 * )
 */
final class ConfigEntityUsingCustomStorage extends ConfigEntityBase {

}
