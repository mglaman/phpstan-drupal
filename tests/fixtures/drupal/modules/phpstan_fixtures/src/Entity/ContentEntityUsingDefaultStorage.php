<?php declare(strict_types=1);

namespace Drupal\phpstan_fixtures\Entity;

use Drupal\Core\Entity\ContentEntityBase;

/**
 * Defines ContentEntityUsingDefaultStorage entity.
 *
 * @ContentEntityType(
 *   id = "content_entity_using_default_storage",
 *   label = @Translation("Content entity using default storage"),
 *   base_table = "content_entity_using_default_storage",
 *   admin_permission = "administer content entity using default storage",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/content_entity_using_default_storage/{content_entity_using_default_storage}",
 *     "collection" = "/admin/content/content-entity-using-default-storage"
 *   },
 * )
 */
final class ContentEntityUsingDefaultStorage extends ContentEntityBase {

}
