<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Deprecations;

use Drupal;
use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\Reflection\ClassReflection;
use ReflectionAttribute;
use function array_key_exists;
use function preg_match;

/**
 * Defines a rule for catching translated categories on field types.
 *
 * @see https://www.drupal.org/node/3375748
 */
final class FieldTypeCategoryRule extends DeprecatedAnnotationsRuleBase
{

    private const DEPRECATION_MESSAGE = 'Using a translatable string as a category for field type is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. See https://www.drupal.org/node/3375748';

    protected function getExpectedInterface(): string
    {
        return FieldItemInterface::class;
    }

    protected function doProcessNode(ClassReflection $reflection, Node\Stmt\Class_ $node, Scope $scope): array
    {
        if (version_compare(Drupal::VERSION, '10.2.0', '<')) {
            return [];
        }

        $errors = [];

        $phpDoc = $reflection->getResolvedPhpDoc();
        if ($phpDoc instanceof ResolvedPhpDocBlock) {
            if ($this->isAnnotated($phpDoc, '@FieldType') && preg_match('/category\s?=\s?@Translation/', $phpDoc->getPhpDocString()) === 1) {
                $errors[] = self::DEPRECATION_MESSAGE;
            }
        }

        $fieldTypeAttributes = $this->getPluginAttribute($reflection, FieldType::class);
        if ($fieldTypeAttributes instanceof ReflectionAttribute) {
            $arguments = $fieldTypeAttributes->getArguments();
            if (array_key_exists('category', $arguments) && $arguments['category'] instanceof TranslatableMarkup) {
                $errors[] = self::DEPRECATION_MESSAGE;
            }
        }

        return $errors;
    }
}
