<?php

namespace Drupal\phpstan_fixtures\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Sample action code to show `context` is ignored since the base class is not context aware.
 *
 * @Action(
 *   id = "action_sample",
 *   label = @Translation("Sample"),
 *   type = "node",
 *   context = {},
 * )
 */
final class ActionSample extends ActionBase {

    public function access($object, AccountInterface $account = null, $return_as_object = false)
    {
        return true;
    }

    public function execute()
    {
        // nothing.
    }
}
