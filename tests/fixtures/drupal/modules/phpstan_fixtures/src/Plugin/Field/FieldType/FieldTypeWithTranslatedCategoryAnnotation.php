<?php

namespace Drupal\phpstan_fixtures\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\DecimalItem;

/**
 * An invalid field type using annotations.
 *
 * @FieldType(
 *   id = "field_type_with_invalid_annotation",
 *   label = @Translation("Field Type With Invalid Annotation"),
 *   description = @Translation("This field type annotation is invalid because the category is not a string."),
 *   category = @Translation("Number"),
 *   default_widget = "number",
 *   default_formatter = "number_decimal"
 * )
 */
class FieldTypeWithTranslatedCategoryAnnotation extends DecimalItem
{

}
