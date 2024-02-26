<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal\Coder;

use Drupal\Core\Form\FormInterface;
use PhpParser\Node;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

final class FormNoPrivatePropertiesRule implements Rule
{

    public function getNodeType(): string
    {
        return ClassPropertyNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof ClassPropertyNode
            || !$node->getClassReflection()->implementsInterface(FormInterface::class)) {
            return [];
        }

        if ($node->isPrivate()) {
            return [
                RuleErrorBuilder::message(
                    'Private properties are note allowed on FormInterface classes due to seralisation.'
                )->tip('See https://www.drupal.org/node/3110266')->build(),
            ];
        }
        if ($node->isReadOnly()) {
            return [
                RuleErrorBuilder::message(
                    'Private properties are note allowed on FormInterface classes due to seralisation.'
                )->tip('See https://www.drupal.org/node/3110266')->build(),
            ];
        }

        return [];
    }

}
