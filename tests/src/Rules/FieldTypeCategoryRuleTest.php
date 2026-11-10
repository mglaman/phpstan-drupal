<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use Drupal;
use mglaman\PHPStanDrupal\Rules\Deprecations\FieldTypeCategoryRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Rule;
use function version_compare;

final class FieldTypeCategoryRuleTest extends DrupalRuleTestCase
{

    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(FieldTypeCategoryRule::class);
    }

    public function testRule(): void
    {
        $errors = [];
        if (version_compare(Drupal::VERSION, '10.2.0', '>=')) {
            $errors[] = [
                'Using a translatable string as a category for field type is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use a field type category plugin ID instead.',
                16,
                'See https://www.drupal.org/node/3375748',
            ];
        }

        $this->analyse([__DIR__ . '/data/field-type-category.php'], $errors);
    }
}
