<?php

namespace DrupalContentEntityTranslation;

use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use function PHPStan\Testing\assertType;

assertType(Node::class, $node = Node::create(['type' => 'page', 'title' => 'foo']));
assertType(Node::class, $node->getTranslation('en'));
assertType(Node::class, $node->getUntranslated());
assertType(Node::class, $node->addTranslation('de', ['title' => 'baz']));

assertType(Term::class, $node = Term::create(['vid' => 'test', 'name' => 'foo']));
assertType(Term::class, $node->getTranslation('en'));
assertType(Term::class, $node->getUntranslated());
assertType(Term::class, $node->addTranslation('de', ['title' => 'baz']));
