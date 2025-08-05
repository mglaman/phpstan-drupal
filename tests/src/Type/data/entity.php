<?php

namespace DrupalEntity;

use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use function PHPStan\Testing\assertType;

assertType('Drupal\node\Entity\Node', $node = Node::create(['type' => 'page', 'title' => 'foo']));
assertType('Drupal\node\Entity\Node', $node->createDuplicate());
assertType('Drupal\node\Entity\Node', $node->enforceIsNew());
assertType('Drupal\node\Entity\Node', $node->enforceIsNew(TRUE));
assertType('Drupal\node\Entity\Node', $node->enforceIsNew(FALSE));
assertType('Drupal\node\Entity\Node', $node->setOriginalId(42));
assertType('Drupal\node\Entity\Node', $node->setOriginalId('42'));
assertType('Drupal\node\Entity\Node', $node->setOriginalId(NULL));
assertType('Drupal\node\Entity\Node|null', Node::load(42));
assertType('Drupal\node\Entity\Node|null', Node::load('42'));
assertType('array<Drupal\node\Entity\Node>', Node::loadMultiple([42, 29]));
assertType('array<Drupal\node\Entity\Node>', Node::loadMultiple(['42', '29']));
assertType('array<Drupal\node\Entity\Node>', Node::loadMultiple(NULL));

assert($id1 instanceof NodeInterface);
assertType('int|string|null', $id1->id());
assert($id2 instanceof NodeInterface);
assert($id2->isNew() === TRUE);
assertType('int|string|null', $id2->id());
assert($id3 instanceof NodeInterface);
assert($id3->isNew() === FALSE);
assertType('int|string', $id3->id());

class NarrowingId extends NodeInterface {
    public function id(): ?string {}
}
assert($idNarrowed1 instanceof NarrowingId);
assertType('string|null', $idNarrowed1->id());
assert($idNarrowed2 instanceof NarrowingId);
assert($idNarrowed2->isNew() === FALSE);
assertType('string', $idNarrowed2->id());
