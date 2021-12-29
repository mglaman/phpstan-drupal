<?php

namespace PhpstanDrupalEntityAccessControlHandler;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\node\Entity\Node;
use function PHPStan\Testing\assertType;

$entityTypeManager = \Drupal::entityTypeManager();
$accessControlHandler = $entityTypeManager->getAccessControlHandler('node');

assertType('bool', $accessControlHandler->access(Node::create(), 'view label'));
assertType('bool', $accessControlHandler->access(Node::create(), 'view label', null));
assertType('bool', $accessControlHandler->access(Node::create(), 'view label', null, false));
assertType(AccessResultInterface::class, $accessControlHandler->access(Node::create(), 'view label', null, true));

assertType('bool', $accessControlHandler->createAccess());
assertType('bool', $accessControlHandler->createAccess('page'));
assertType('bool', $accessControlHandler->createAccess('page', null));
assertType('bool', $accessControlHandler->createAccess('page', null, []));
assertType('bool', $accessControlHandler->createAccess('page', null, [], false));
assertType(AccessResultInterface::class, $accessControlHandler->createAccess('page', null, [], true));

$definition = FieldDefinition::create('string');
assertType('bool', $accessControlHandler->fieldAccess('add', $definition));
assertType('bool', $accessControlHandler->fieldAccess('add', $definition, null));
assertType('bool', $accessControlHandler->fieldAccess('add', $definition, null, null));
assertType('bool', $accessControlHandler->fieldAccess('add', $definition, null, null, false));
assertType(AccessResultInterface::class, $accessControlHandler->fieldAccess('add', $definition, null, null, true));

