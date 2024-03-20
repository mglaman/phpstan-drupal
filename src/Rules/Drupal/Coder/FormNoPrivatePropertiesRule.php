<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal\Coder;

use Drupal\Core\Form\FormInterface;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<ClassPropertyNode>
 */
final class FormNoPrivatePropertiesRule implements Rule
{

    public function getNodeType(): string
    {
        return ClassPropertyNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->getClassReflection()->implementsInterface(FormInterface::class)) {
            return [];
        }

        $errors = [];
        if ($node->isPrivate()) {
            $errors[] = RuleErrorBuilder::message(
                'Private properties are not allowed on FormInterface classes due to serialisation.'
            )->tip('See https://www.drupal.org/node/3110266')->build();
        }
        if ($node->isReadOnly()) {
            $errors[] = RuleErrorBuilder::message(
                'Read only properties are not allowed on FormInterface classes due to serialisation.'
            )->tip('See https://www.drupal.org/node/3110266')->build();
        }
        return $errors;
    }
}
