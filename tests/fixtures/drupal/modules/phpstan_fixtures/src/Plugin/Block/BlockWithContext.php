<?php

namespace Drupal\phpstan_fixtures\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a context-aware block.
 *
 * @Block(
 *   id = "test_context_aware",
 *   admin_label = @Translation("Test context-aware block"),
 *   context = {
 *     "user" = @ContextDefinition("entity:user", required = FALSE,
 *       label = @Translation("User Context"), constraints = { "NotNull" = {} }
 *     ),
 *   }
 * )
 */
class BlockWithContext extends BlockBase {

    /**
     * {@inheritdoc}
     */
    public function build() {
        /** @var \Drupal\user\UserInterface|null $user */
        $user = $this->getContextValue('user');
        return [
            '#prefix' => '<div id="' . $this->getPluginId() . '--username">',
            '#suffix' => '</div>',
            '#markup' => $user ? $user->getAccountName() : 'No context mapping selected.',
        ];
    }

}
