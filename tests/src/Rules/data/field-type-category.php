<?php

namespace FieldTypeCategory;

use Drupal\Core\Field\Plugin\Field\FieldType\DecimalItem;

/**
 * @FieldType(
 *   id = "translated_category",
 *   label = @Translation("Translated category"),
 *   category = @Translation("Number"),
 *   default_widget = "number",
 *   default_formatter = "number_decimal"
 * )
 */
class TranslatedCategory extends DecimalItem
{
}

/**
 * @FieldType(
 *   id = "string_category",
 *   label = @Translation("String category"),
 *   category = "number",
 *   default_widget = "number",
 *   default_formatter = "number_decimal"
 * )
 */
class StringCategory extends DecimalItem
{
}

/**
 * Mentions category = @Translation("Number") outside a @FieldType annotation.
 */
class NotAFieldTypeAnnotation extends DecimalItem
{
}
