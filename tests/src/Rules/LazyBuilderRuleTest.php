<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Drupal\RenderCallback\LazyBuilderRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Rule;

final class LazyBuilderRuleTest extends DrupalRuleTestCase
{

    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(LazyBuilderRule::class);
    }

    public function testRule(): void
    {
        $structureTip = "Use ['#lazy_builder' => [callback, [arguments]]].";
        $argumentTip = 'Pass an ID or another scalar value, and load the object in the callback. Drupal puts the arguments in the placeholder.';

        $this->analyse([__DIR__ . '/data/lazy-builder.php'], [
            [
                '#create_placeholder is TRUE, but there is no #lazy_builder callback. Drupal throws a LogicException when it renders this element.',
                40,
                'Add a #lazy_builder callback that builds the element, or remove #create_placeholder.',
            ],
            [
                '#lazy_builder must be an array of two values: the callback, and an array of arguments for it.',
                43,
                $structureTip,
            ],
            [
                'The #lazy_builder arguments must be an array, string given.',
                46,
                $structureTip,
            ],
            [
                '#lazy_builder argument 0 is Drupal\Core\Entity\EntityInterface, but only scalar values and NULL are allowed.',
                52,
                $argumentTip,
            ],
            [
                '#lazy_builder argument 1 is array<int, int>, but only scalar values and NULL are allowed.',
                52,
                $argumentTip,
            ],
            [
                'An element with #lazy_builder can only have #cache, #create_placeholder, #lazy_builder_preview, #placeholder_strategy_denylist, #preview and #weight next to it. Found: #title, child.',
                55,
                'Return these properties and children from the #lazy_builder callback instead.',
            ],
            [
                '#lazy_builder callback LazyBuilder\Builder::buildString() returns string, but it must return a render array.',
                60,
            ],
            [
                '#create_placeholder is TRUE, but there is no #lazy_builder callback. Drupal throws a LogicException when it renders this element.',
                123,
                'Add a #lazy_builder callback that builds the element, or remove #create_placeholder.',
            ],
            [
                '#lazy_builder arguments contain Drupal\Core\Entity\EntityInterface, but only scalar values and NULL are allowed.',
                126,
                $argumentTip,
            ],
            [
                'An element with #lazy_builder can only have #cache, #create_placeholder, #lazy_builder_preview, #placeholder_strategy_denylist, #preview and #weight next to it. Found: 0, 1.',
                129,
                'Return these properties and children from the #lazy_builder callback instead.',
            ],
        ]);
    }
}
