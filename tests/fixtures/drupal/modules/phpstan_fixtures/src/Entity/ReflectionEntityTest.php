<?php declare(strict_types=1);

namespace Drupal\phpstan_fixtures\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * @property \Drupal\Core\Field\EntityReferenceFieldItemListInterface $user_id
 */
final class ReflectionEntityTest extends ContentEntityBase {

    public static function baseFieldDefinitions(EntityTypeInterface $entity_type)
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('User ID'))
            ->setDescription(t('The ID of the associated user.'))
            ->setSetting('target_type', 'user')
            ->setSetting('handler', 'default')
            // Default EntityTest entities to have the root user as the owner, to
            // simplify testing.
            ->setDefaultValue([0 => ['target_id' => 1]])
            ->setTranslatable(TRUE)
            ->setDefaultValueCallback([static::class, 'getDefaultEntityOwner'])
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -1,
                'settings' => [
                    'match_operator' => 'CONTAINS',
                    'size' => '60',
                    'placeholder' => '',
                ],
            ]);
        return $fields;
    }

    public function getOwnerId(): int {
        return $this->get('user_id')->target_id;
    }

    public function setOwnerId($uid): ReflectionEntityTest {
        $this->set('user_id', $uid);

        return $this;
    }

    public function getOwner(): UserInterface {
        return $this->get('user_id')->entity;
    }

    public function setOwner(UserInterface $account): ReflectionEntityTest {
        $this->set('user_id', $account);

        return $this;
    }

    public static function getDefaultEntityOwner(): int {
        return \Drupal::currentUser()->id();
    }

}
