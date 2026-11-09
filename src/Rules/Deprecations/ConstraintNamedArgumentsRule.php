<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Deprecations;

use Drupal;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use ReflectionProperty;
use function array_diff;
use function array_map;
use function count;
use function implode;
use function sprintf;
use function str_contains;
use function version_compare;

/**
 * Reports constraint plugins that cannot get their options on Drupal 12.
 *
 * Drupal 11.4 deprecated passing the options of a constraint plugin as an
 * array. Drupal 11.4 and 11.5 pass them as named arguments if the constructor
 * has the HasNamedArguments attribute, and Drupal 12 always does. A plugin
 * without such a constructor fails with an "Unknown named parameter" error.
 *
 * @see https://www.drupal.org/node/3554746
 *
 * @implements Rule<InClassNode>
 */
final class ConstraintNamedArgumentsRule implements Rule
{

    private const SYMFONY_CONSTRAINT = 'Symfony\Component\Validator\Constraint';

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (version_compare(Drupal::VERSION, '11.4', '<')) {
            return [];
        }
        $class = $node->getClassReflection();
        // Symfony's base class is abstract, so this only matches subclasses.
        if (!$class->isClass() || $class->isAbstract() || !$class->is(self::SYMFONY_CONSTRAINT)) {
            return [];
        }
        // Plugins with a factory method get their options from it.
        if ($class->implementsInterface('Drupal\Core\Plugin\ContainerFactoryPluginInterface') || !$this->isPlugin($class)) {
            return [];
        }

        // The public properties are the options, except the ones of Symfony's
        // base class.
        $native = $class->getNativeReflection();
        $options = [];
        foreach ($native->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic() && $property->getDeclaringClass()->getName() !== self::SYMFONY_CONSTRAINT) {
                $options[] = $property->getName();
            }
        }
        if ($options === []) {
            return [];
        }

        $constructor = $native->getConstructor();
        if ($constructor === null
            || $constructor->getDeclaringClass()->getName() === self::SYMFONY_CONSTRAINT
            || count($constructor->getAttributes('Symfony\Component\Validator\Attribute\HasNamedArguments')) === 0
        ) {
            return [
                RuleErrorBuilder::message(sprintf(
                    'Constraint plugin %s has options, but no constructor with the #[HasNamedArguments] attribute. Drupal 12 passes the options as named arguments, so the constraint fails when it gets options. Add a constructor with a parameter for each option and the #[HasNamedArguments] attribute.',
                    $class->getDisplayName()
                ))
                    ->tip('See https://www.drupal.org/node/3554746')
                    ->identifier('constraintNamedArguments.missingConstructor')
                    ->build(),
            ];
        }

        foreach ($constructor->getParameters() as $parameter) {
            if ($parameter->isVariadic()) {
                return [];
            }
        }
        $parameters = array_map(static fn ($parameter) => $parameter->getName(), $constructor->getParameters());
        $missing = array_diff($options, $parameters);
        if ($missing !== []) {
            return [
                RuleErrorBuilder::message(sprintf(
                    'The constructor of constraint plugin %s has no parameter for these options: %s. Drupal passes the options as named arguments, so the constraint fails when it gets one of them.',
                    $class->getDisplayName(),
                    implode(', ', $missing)
                ))
                    ->tip('See https://www.drupal.org/node/3554746')
                    ->identifier('constraintNamedArguments.missingParameter')
                    ->build(),
            ];
        }

        return [];
    }

    /**
     * Checks for the Constraint attribute or annotation of Drupal plugins.
     */
    private function isPlugin(ClassReflection $class): bool
    {
        if (count($class->getNativeReflection()->getAttributes('Drupal\Core\Validation\Attribute\Constraint')) > 0) {
            return true;
        }
        $docComment = $class->getNativeReflection()->getDocComment();
        return $docComment !== false && str_contains($docComment, '@Constraint(');
    }
}
