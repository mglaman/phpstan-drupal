<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Deprecations;

use Drupal;
use Drupal\Core\Field\FieldItemInterface;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\RuleErrorBuilder;
use function preg_match;
use function version_compare;

/**
 * Reports @FieldType annotations that use a translatable category.
 *
 * The FieldType attribute types $category as a string, so PHPStan already
 * reports a TranslatableMarkup category there.
 *
 * @see https://www.drupal.org/node/3375748
 */
final class FieldTypeCategoryRule extends DeprecatedAnnotationsRuleBase
{

    protected function getExpectedInterface(): string
    {
        return FieldItemInterface::class;
    }

    protected function doProcessNode(ClassReflection $reflection, Node\Stmt\Class_ $node, Scope $scope): array
    {
        if (version_compare(Drupal::VERSION, '10.2.0', '<')) {
            return [];
        }

        $phpDoc = $reflection->getResolvedPhpDoc();
        if ($phpDoc === null) {
            return [];
        }

        if (preg_match('/@FieldType\s*\(.*\bcategory\s*=\s*@Translation\b/s', $phpDoc->getPhpDocString()) !== 1) {
            return [];
        }

        return [
            RuleErrorBuilder::message('Using a translatable string as a category for field type is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use a field type category plugin ID instead.')
                ->identifier('fieldTypeCategory.translatable')
                ->tip('See https://www.drupal.org/node/3375748')
                ->build(),
        ];
    }
}
