<?php

namespace Bug457;

use Drupal\Core\Render\Element\Checkboxes;
use Drupal\Core\Render\Element\Radios;

function union_class(bool $multiple): array {
    [$class, $type] = $multiple ? [Checkboxes::class, 'checkboxes'] : [Radios::class, 'radios'];
    return [
        '#pre_render' => [
            [$class, 'preRenderCompositeFormElement'],
        ],
    ];
}

function ternary_class(bool $multiple): array {
    $class = $multiple ? Checkboxes::class : Radios::class;
    return ['#pre_render' => [[$class, 'preRenderCompositeFormElement']]];
}

function union_missing_method(bool $multiple): array {
    $class = $multiple ? Checkboxes::class : Radios::class;
    return ['#pre_render' => [[$class, 'processCheckboxes']]];
}
