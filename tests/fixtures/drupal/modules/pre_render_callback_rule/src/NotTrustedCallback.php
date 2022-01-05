<?php declare(strict_types=1);

namespace Drupal\pre_render_callback_rule;

final class NotTrustedCallback {

    public static function unsafeCallback(array $element): array {
        return $element;
    }

}
