<?php

namespace Bug396a;

function () {
    $query = \Drupal::entityQuery('user')
        ->condition('status', 1)
        ->condition('uid', 1, '<>')
        ->condition('field_profile_visibility', 1)
        ->condition('field_account_type', '', '<>')
        ->condition('field_last_name.value', '', '<>');
    if( isset($qparam['expertise']) && !empty($qparam['expertise']) ) {
        $query->condition('field_field_expertise.entity.tid', $qparam['expertise']);
    }
    if( isset($qparam['origin']) && !empty($qparam['origin']) ) {
        $query->condition('field_place_origin.entity:taxonomy_term.field_country_tags', $qparam['origin']);
    }
    if( isset($qparam['place_residence']) && !empty($qparam['place_residence']) ) {
        $query->condition('field_place_residence.entity:taxonomy_term.field_country_tags', $qparam['place_residence']);
    }
    if( isset($qparam['city']) && !empty($qparam['city']) ) {
        $query->condition('field_city', $qparam['city'], 'CONTAINS');
    }
    if( isset($qparam['user_type']) && !empty($qparam['user_type']) ) {
        $query->condition('field_account_type.entity.tid', $qparam['user_type']);
    }
    $users = $query->execute();
};
function () {
    $query = \Drupal::entityQuery('user')
        ->accessCheck(FALSE)
        ->condition('status', 1)
        ->condition('uid', 1, '<>')
        ->condition('field_profile_visibility', 1)
        ->condition('field_account_type', '', '<>')
        ->condition('field_last_name.value', '', '<>');
    if( isset($qparam['expertise']) && !empty($qparam['expertise']) ) {
        $query->condition('field_field_expertise.entity.tid', $qparam['expertise']);
    }
    if( isset($qparam['origin']) && !empty($qparam['origin']) ) {
        $query->condition('field_place_origin.entity:taxonomy_term.field_country_tags', $qparam['origin']);
    }
    if( isset($qparam['place_residence']) && !empty($qparam['place_residence']) ) {
        $query->condition('field_place_residence.entity:taxonomy_term.field_country_tags', $qparam['place_residence']);
    }
    if( isset($qparam['city']) && !empty($qparam['city']) ) {
        $query->condition('field_city', $qparam['city'], 'CONTAINS');
    }
    if( isset($qparam['user_type']) && !empty($qparam['user_type']) ) {
        $query->condition('field_account_type.entity.tid', $qparam['user_type']);
    }
    $users = $query->execute();
};
function () {
    $query = \Drupal::entityQuery('user')
        ->condition('status', 1)
        ->condition('uid', 1, '<>')
        ->condition('field_profile_visibility', 1)
        ->condition('field_account_type', '', '<>')
        ->condition('field_last_name.value', '', '<>');
    if( isset($qparam['expertise']) && !empty($qparam['expertise']) ) {
        $query->condition('field_field_expertise.entity.tid', $qparam['expertise']);
    }
    if( isset($qparam['origin']) && !empty($qparam['origin']) ) {
        $query->condition('field_place_origin.entity:taxonomy_term.field_country_tags', $qparam['origin']);
    }
    if( isset($qparam['place_residence']) && !empty($qparam['place_residence']) ) {
        $query->condition('field_place_residence.entity:taxonomy_term.field_country_tags', $qparam['place_residence']);
    }
    if( isset($qparam['city']) && !empty($qparam['city']) ) {
        $query->condition('field_city', $qparam['city'], 'CONTAINS');
    }
    if( isset($qparam['user_type']) && !empty($qparam['user_type']) ) {
        $query->condition('field_account_type.entity.tid', $qparam['user_type']);
    }
    $query->accessCheck(FALSE);
    $users = $query->execute();
};
