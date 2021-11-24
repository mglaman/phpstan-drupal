<?php declare(strict_types=1);

namespace Drupal\phpstan_fixtures\Entity;

use Drupal\Core\Entity\ContentEntityBase;

/**
 * Defines ContentEntityUsingCustomStorage entity.
 *
 * @ContentEntityType(
 *   id = "content_entity_using_custom_storage",
 *   label = @Translation("Content entity using custom storage"),
 *   base_table = "content_entity_using_default_storage",
 *   admin_permission = "administer content entity using custom storage",
 *   handlers = {
 *     "storage" = "Drupal\phpstan_fixtures\CustomContentEntityStorage",
 *   }
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/content_entity_using_custom_storage/{content_entity_using_custom_storage}",
 *     "collection" = "/admin/content/content-entity-using-custom-storage"
 *   },
 * )
 */
final class ContentEntityUsingCustomStorage extends ContentEntityBase {

}
