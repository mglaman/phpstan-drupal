<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<ClassPropertyNode>
 */
final class DependencySerializationTraitPropertyRule implements Rule
{

    public function __construct(
        private readonly PhpVersion $phpVersion
    ) {
    }

    public function getNodeType(): string
    {
        return ClassPropertyNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $node->getClassReflection();
        if (!$classReflection->hasTraitUse(DependencySerializationTrait::class)) {
            return [];
        }

        $errors = [];
        if ($node->isPrivate()) {
            $errors[] = RuleErrorBuilder::message(
                sprintf(
                    '%s does not support private properties.',
                    DependencySerializationTrait::class
                )
            )->tip('See https://www.drupal.org/node/3110266')
            ->identifier('dependencySerializationTraitProperty.unsupportedPrivateProperty')
            ->build();
        }
        if ($node->isReadOnly() && $this->isReadOnlyPropertyBroken($node, $classReflection)) {
            $errors[] = RuleErrorBuilder::message(
                sprintf(
                    'Read-only properties are incompatible with %s when the trait is used by a parent class on PHP < 8.4.',
                    DependencySerializationTrait::class
                )
            )->tip(sprintf(
                '%s::__wakeup() cannot initialize a read-only property declared in a child class. Use the trait in this class directly, or remove the readonly modifier.',
                DependencySerializationTrait::class
            ))
            ->identifier('dependencySerializationTraitProperty.unsupportedReadOnlyProperty')
            ->build();
        }
        return $errors;
    }

    private function isReadOnlyPropertyBroken(ClassPropertyNode $node, ClassReflection $classReflection): bool
    {
        // PHP 8.4 allows initializing a read-only property from a parent
        // class scope, which makes __wakeup() work in all cases.
        if ($this->phpVersion->getVersionId() >= 80400) {
            return false;
        }

        // The trait's __sleep() skips non-object values, so properties that
        // can never hold an object are serialized as-is and never touched.
        $nativeType = $node->getNativeType();
        if ($nativeType !== null && $nativeType->isObject()->no()) {
            return false;
        }

        // When the declaring class composes the trait itself, __wakeup() runs
        // in the declaring scope and may initialize the read-only property.
        return !$this->classComposesTrait($classReflection);
    }

    private function classComposesTrait(ClassReflection $classReflection): bool
    {
        foreach ($classReflection->getTraits() as $trait) {
            if ($trait->getName() === DependencySerializationTrait::class || $this->classComposesTrait($trait)) {
                return true;
            }
        }
        return false;
    }
}
