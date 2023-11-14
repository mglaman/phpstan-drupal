<?php

namespace EntityReferenceRevisionsExample;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class Foo {
    private EntityTypeManagerInterface $entityTypeManager;
    public function __construct(EntityTypeManagerInterface $entityTypeManager)
    {
        $this->entityTypeManager = $entityTypeManager;
    }
    public function deleteUnusedRevision(ContentEntityInterface $composite_revision) {
        $composite_storage = $this->entityTypeManager->getStorage($composite_revision->getEntityTypeId());

        if ($composite_revision->isDefaultRevision()) {
            $count = $composite_storage
                ->getQuery()
                ->accessCheck(FALSE)
                ->allRevisions()
                ->condition($composite_storage->getEntityType()->getKey('id'), $composite_revision->id())
                ->count()
                ->execute();
            if ($count <= 1) {
                $composite_revision->delete();
                return TRUE;
            }
        }
        else {
            // Delete the revision if this is not the default one.
            $composite_storage->deleteRevision($composite_revision->getRevisionId());
            return TRUE;
        }

        return FALSE;
    }
}
