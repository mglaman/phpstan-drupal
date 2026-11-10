<?php

namespace EntityRepository;

use Drupal\node\Entity\Node;
use function PHPStan\Testing\assertType;

$entityRepository = \Drupal::service('entity.repository');

/** @var string $someEntityType */
/** @var 'node'|'block' $nodeOrBlock */

assertType(
    'Drupal\node\Entity\Node|null',
    $entityRepository->loadEntityByUuid('node', '3f205175-04f7-4f57-b48b-9799299252c3')
);

assertType(
    'Drupal\Core\Entity\Entity\EntityViewMode|null',
    $entityRepository->loadEntityByConfigTarget('entity_view_mode', 'media.default')
);
assertType(
    'Drupal\Core\Entity\EntityInterface|null',
    $entityRepository->loadEntityByConfigTarget($someEntityType, 'media.default')
);

assertType(
    'Drupal\node\Entity\Node',
    $entityRepository->getTranslationFromContext(Node::create())
);

assertType(
    'Drupal\node\Entity\Node|null',
    $entityRepository->getActive('node', 5)
);
assertType(
    'Drupal\Core\Entity\EntityInterface|null',
    $entityRepository->getActive($someEntityType, 5)
);
assertType(
    'Drupal\Core\Entity\EntityInterface|null',
    $entityRepository->getActive('not_an_entity_type', 5)
);
assertType(
    'Drupal\block\Entity\Block|Drupal\node\Entity\Node|null',
    $entityRepository->getActive($nodeOrBlock, 5)
);

assertType(
    'array<int, Drupal\node\Entity\Node>',
    $entityRepository->getActiveMultiple('node', [5])
);
assertType(
    'array<string, Drupal\block\Entity\Block>',
    $entityRepository->getActiveMultiple('block', ['foo'])
);

assertType(
    'array<Drupal\Core\Entity\EntityInterface>',
    $entityRepository->getActiveMultiple($someEntityType, [5])
);
assertType(
    'array<Drupal\Core\Entity\EntityInterface>',
    $entityRepository->getActiveMultiple('not_an_entity_type', [5])
);
assertType(
    'array<int|string, Drupal\block\Entity\Block|Drupal\node\Entity\Node>',
    $entityRepository->getActiveMultiple($nodeOrBlock, [5])
);

assertType(
    'Drupal\node\Entity\Node|null',
    $entityRepository->getCanonical('node', 5)
);
assertType(
    'Drupal\Core\Entity\EntityInterface|null',
    $entityRepository->getCanonical($someEntityType, 5)
);

assertType(
    'array<int, Drupal\node\Entity\Node>',
    $entityRepository->getCanonicalMultiple('node', [5])
);
assertType(
    'array<Drupal\Core\Entity\EntityInterface>',
    $entityRepository->getCanonicalMultiple($someEntityType, [5])
);
