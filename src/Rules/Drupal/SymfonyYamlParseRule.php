<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Detects direct usage of Symfony's YAML parser and suggests the Drupal wrapper.
 *
 * Drupal\Component\Serialization\Yaml::decode() should be used instead of
 * Symfony\Component\Yaml\Yaml::parse() because it wraps exceptions as
 * InvalidDataTypeException and applies consistent parse flags.
 *
 * @implements Rule<StaticCall>
 */
class SymfonyYamlParseRule implements Rule
{
    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!($node->class instanceof Node\Name\FullyQualified)) {
            return [];
        }

        $className = (string) $node->class;
        if ($className !== 'Symfony\Component\Yaml\Yaml') {
            return [];
        }

        if (!($node->name instanceof Node\Identifier)) {
            return [];
        }

        if ((string) $node->name !== 'parse') {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                'Avoid calling Symfony\Component\Yaml\Yaml::parse() directly. Use \Drupal\Component\Serialization\Yaml::decode() instead, which handles exceptions consistently and applies the correct parse flags.'
            )
            ->identifier('drupal.symfonyYamlParse')
            ->build(),
        ];
    }
}
