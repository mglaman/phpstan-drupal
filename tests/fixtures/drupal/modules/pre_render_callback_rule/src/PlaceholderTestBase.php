<?php declare(strict_types=1);

namespace Drupal\pre_render_callback_rule;

use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Mirrors core's RendererTestBase, which also declares PlaceholdersTest.
 */
abstract class PlaceholderTestBase {

}

/**
 * PHP cannot autoload this class: its file is named after another class.
 */
class PlaceholderCallbacks implements TrustedCallbackInterface {

    public static function callback(): array {
        return ['#markup' => 'trusted'];
    }

    public static function unlistedCallback(): array {
        return ['#markup' => 'not trusted'];
    }

    public static function trustedCallbacks() {
        return ['callback'];
    }

}
