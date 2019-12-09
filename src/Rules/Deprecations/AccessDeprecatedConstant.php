<?php declare(strict_types = 1);

namespace PHPStan\Rules\Deprecations;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\Broker;
use PHPStan\Reflection\DeprecatableReflection;

class AccessDeprecatedConstant implements \PHPStan\Rules\Rule
{
    /** @var Broker */
    private $broker;
    public function __construct(Broker $broker)
    {
        $this->broker = $broker;
    }

    public function getNodeType(): string
    {
        return Node\Expr\ConstFetch::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof Node\Expr\ConstFetch);
        $class = $scope->getClassReflection();
        if ($class !== null && $class->isDeprecated()) {
            return [];
        }
        $trait = $scope->getTraitReflection();
        if ($trait !== null && $trait->isDeprecated()) {
            return [];
        }
        $function = $scope->getFunction();
        if ($function instanceof DeprecatableReflection && $function->isDeprecated()) {
            return [];
        }

        // nikic/php-parser does not have any comments above the comment.
        // possibly due to PHP's internal reflection capabilities?
        $deprecatedConstants = [
            'DATETIME_STORAGE_TIMEZONE' => 'Deprecated in Drupal 8.5.x and will be removed before Drupal 9.0.x. Use \Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::STORAGE_TIMEZONE instead.',
            'DATETIME_DATETIME_STORAGE_FORMAT' => 'Deprecated in Drupal 8.5.x and will be removed before Drupal 9.0.x. Use \Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::DATETIME_STORAGE_FORMAT instead.',
            'DATETIME_DATE_STORAGE_FORMAT' => 'Deprecated in Drupal 8.5.x and will be removed before Drupal 9.0.x. Use \Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::DATE_STORAGE_FORMAT instead.',
        ];
        $constantName = $this->broker->resolveConstantName($node->name, $scope);
        if (isset($deprecatedConstants[$constantName])) {
            return [
                sprintf('Call to deprecated constant %s: %s', $constantName, $deprecatedConstants[$constantName])
            ];
        }
        return [];
    }

}
