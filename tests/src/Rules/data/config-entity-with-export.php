<?php declare(strict_types=1);

namespace Drupal\phpstan_fixtures\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the ConfigTest configuration entity.
 *
 * @ConfigEntityType(
 *   id = "config_test",
 *   label = @Translation("Test configuration"),
 *   config_export = {
 *     "id",
 *     "label",
 *     "weight",
 *     "style",
 *     "size",
 *     "size_value",
 *     "protected_property",
 *   },
 * )
 */
final class ConfigWithExport extends ConfigEntityBase {

}
