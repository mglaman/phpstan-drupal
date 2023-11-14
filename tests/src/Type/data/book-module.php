<?php

namespace BookModuleProperty;

use Drupal\node\Entity\Node;
use function PHPStan\Testing\assertType;

$node = Node::create(['type' => 'book']);
assertType('array{nid?: int|numeric-string, bid?: \'new\'|int|numeric-string|false, original_bid?: int|numeric-string, pid?: int|numeric-string, parent_depth_limit?: int|numeric-string, has_children?: bool|int|numeric-string, weight?: int|numeric-string, depth?: int|numeric-string, p1?: int|numeric-string, p2?: int|numeric-string, p3?: int|numeric-string, p4?: int|numeric-string, p5?: int|numeric-string, p6?: int|numeric-string, p7?: int|numeric-string, p8?: int|numeric-string, p9?: int|numeric-string, link_path?: string, link_title?: string}', $node->book);
