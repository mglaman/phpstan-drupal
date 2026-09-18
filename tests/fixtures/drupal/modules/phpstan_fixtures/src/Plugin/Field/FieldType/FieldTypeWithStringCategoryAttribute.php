<?php

namespace Drupal\phpstan_fixtures\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\Plugin\Field\FieldType\DecimalItem;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Represents a field type with a translated category argument.
 */
#[FieldType(
    id: "field_type_with_valid_attribute",
    label: new TranslatableMarkup("Field Type With Valid Attribute"),
    description: [
        new TranslatableMarkup("This attribute is valid because the category is a string."),
    ],
    category: "number",
    weight: -10,
    default_widget: "number",
    default_formatter: "number_decimal"
)]
class FieldTypeWithStringCategoryAttribute extends DecimalItem
{

}
