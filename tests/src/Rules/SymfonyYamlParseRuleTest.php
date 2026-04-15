<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Drupal\SymfonyYamlParseRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;

final class SymfonyYamlParseRuleTest extends DrupalRuleTestCase
{
    protected function getRule(): \PHPStan\Rules\Rule
    {
        return new SymfonyYamlParseRule();
    }

    public function testRule(): void
    {
        $this->analyse(
            [__DIR__ . '/data/symfony-yaml-parse.php'],
            [
                [
                    'Avoid calling Symfony\Component\Yaml\Yaml::parse() directly. Use \Drupal\Component\Serialization\Yaml::decode() instead, which respects the yaml_parser_class setting.',
                    6,
                ],
                [
                    'Avoid calling Symfony\Component\Yaml\Yaml::parse() directly. Use \Drupal\Component\Serialization\Yaml::decode() instead, which respects the yaml_parser_class setting.',
                    9,
                ],
            ]
        );
    }
}
