<?php

namespace mglaman\PHPStanDrupal\Tests\Rules;

use Generator;
use mglaman\PHPStanDrupal\Rules\Deprecations\FieldTypeCategoryRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;

/**
 * Test the rule to detect translated field type categories.
 */
class FieldTypeCategoryRuleTest extends DrupalRuleTestCase
{

    /**
     * {@inheritdoc}
     */
    protected function getRule(): Rule
    {
        return new FieldTypeCategoryRule(
            self::getContainer()->getByType(ReflectionProvider::class)
        );
    }

    /**
     * @dataProvider ruleData
     */
    public function testRule(string $path, array $errorMessages): void
    {
        $this->analyse([$path], $errorMessages);
    }

    public static function ruleData(): Generator
    {
        yield [
            __DIR__ . '/../../fixtures/drupal/core/lib/Drupal/Core/Field/Plugin/Field/FieldType/FloatItem.php',
            [],
        ];

        yield [
            __DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/src/Plugin/Field/FieldType/FieldTypeWithTranslatedCategoryAnnotation.php',
            [
                [
                    'Using a translatable string as a category for field type is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. See https://www.drupal.org/node/3375748',
                    19,
                ],
            ]
        ];

        yield [
            __DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/src/Plugin/Field/FieldType/FieldTypeWithStringCategoryAttribute.php',
            []
        ];

        yield [
            __DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/src/Plugin/Field/FieldType/FieldTypeWithTranslatedCategoryAttribute.php',
            [
                [
                    'Using a translatable string as a category for field type is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. See https://www.drupal.org/node/3375748',
                    12,
                ],
            ]
        ];
    }
}
