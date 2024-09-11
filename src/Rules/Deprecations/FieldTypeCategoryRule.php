<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Deprecations;

use Drupal;
use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionAttribute;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\Reflection\ClassReflection;
use function array_key_exists;
use function preg_match;

/**
 *
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
        if (!$this->ruleApplies()) {
            return [];
        }

        $errors = [];

        $phpDoc = $reflection->getResolvedPhpDoc();
        if ($phpDoc instanceof ResolvedPhpDocBlock) {
            if ($this->hasFieldTypeAnnotation($phpDoc) && preg_match('/category\s?=\s?@Translation/', $phpDoc->getPhpDocString()) === 1) {
                $errors[] = self::DEPRECATION_MESSAGE;
            }
        }

        $fieldTypeAttributes = $this->getFieldTypeAttributes($reflection);
        if ($fieldTypeAttributes instanceof ReflectionAttribute) {
            $arguments = $fieldTypeAttributes->getArguments();
            if (array_key_exists('category', $arguments) && $arguments['category'] instanceof TranslatableMarkup) {
                $errors[] = self::DEPRECATION_MESSAGE;
            }
        }

        return $errors;
    }

    private function ruleApplies(): bool
    {
        [$major, $minor] = array_map(fn($x) => (int) $x, explode('.', Drupal::VERSION, 2));

        return $major > 10 || ($major === 10 && $minor >= 2);
    }

    private function hasFieldTypeAnnotation(ResolvedPhpDocBlock $phpDoc): bool
    {
        foreach ($phpDoc->getPhpDocNodes() as $docNode) {
            foreach ($docNode->children as $childNode) {
                if (($childNode instanceof PhpDocTagNode) && $childNode->name === '@FieldType') {
                    return true;
                }
            }
        }
        return false;
    }

    private function getFieldTypeAttributes(ClassReflection $reflection): ?ReflectionAttribute
    {
        $attributes = $reflection->getNativeReflection()->getAttributes(FieldType::class);
        return $attributes[0] ?? null;
    }
}
