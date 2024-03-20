<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<ClassPropertyNode>
 */
final class DependencySerializationTraitPropertyRule implements Rule
{

    public function getNodeType(): string
    {
        return ClassPropertyNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->getClassReflection()->hasTraitUse(DependencySerializationTrait::class)) {
            return [];
        }

        $errors = [];
        if ($node->isPrivate()) {
            $errors[] = RuleErrorBuilder::message(
                sprintf(
                    '%s does not support private properties.',
                    DependencySerializationTrait::class
                )
            )->tip('See https://www.drupal.org/node/3110266')->build();
        }
        if ($node->isReadOnly()) {
            $errors[] = RuleErrorBuilder::message(
                sprintf(
                    'Read-only properties are incompatible with %s.',
                    DependencySerializationTrait::class
                )
            )->tip('See https://www.drupal.org/node/3110266')->build();
        }
        return $errors;
    }
}
