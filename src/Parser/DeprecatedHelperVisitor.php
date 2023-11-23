<?php

namespace mglaman\PHPStanDrupal\Parser;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;

class DeprecatedHelperVisitor extends NodeVisitorAbstract implements NodeVisitor
{

    private ?Node\Arg $deprecatedCall = null;

    public function enterNode(Node $node)
    {

        if ($node instanceof Node\Expr\StaticCall) {
            if ($node->class instanceof Node\Name\FullyQualified && $node->class->isFullyQualified() && $node->class->toString() === 'Drupal\Component\Utility\DeprecationHelper') {
                $this->deprecatedCall = $node->getArgs()[2];
                return null;
            }
        }

        if ($this->deprecatedCall !== null && $node instanceof Node\Arg && $this->isSavedArgument($node)) {
            $this->deprecatedCall = null;
            return NodeTraverser::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
        }

        return null;
    }

    private function isSavedArgument(Node\Arg $node): bool
    {
        if ($this->deprecatedCall !== null && $node->getAttributes() === $this->deprecatedCall->getAttributes()) {
            return true;
        }
        return false;
    }
}
