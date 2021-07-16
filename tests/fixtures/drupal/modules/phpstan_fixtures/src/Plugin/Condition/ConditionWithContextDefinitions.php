<?php

namespace Drupal\phpstan_fixtures\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;

/**
 * @Condition(
 *   id = "condition_with_context",
 *   label = @Translation("Condition with context"),
 *   context_definitions = {
 *     "user" = @ContextDefinition("entity:user", label = @Translation("User"))
 *   },
 * );
 */

final class ConditionWithContextDefinitions extends ConditionPluginBase {

    public function evaluate()
    {
        return true;
    }

    public function summary()
    {
        return 'sample file';
    }
}
