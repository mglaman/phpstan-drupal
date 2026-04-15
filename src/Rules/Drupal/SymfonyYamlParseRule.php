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
 * Drupal\Component\Serialization\Yaml::decode() respects the yaml_parser_class
 * setting, allowing sites to override the parser. Using Symfony's class directly
 * bypasses that setting.
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
                'Avoid calling Symfony\Component\Yaml\Yaml::parse() directly. Use \Drupal\Component\Serialization\Yaml::decode() instead, which respects the yaml_parser_class setting.'
            )
            ->identifier('drupal.symfonyYamlParse')
            ->build(),
        ];
    }
}
