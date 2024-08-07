<?php

namespace DrupalEntityFields;

use Drupal\comment\Plugin\Field\FieldType\CommentItem;
use Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem;
use Drupal\Core\Field\Plugin\Field\FieldType\ChangedItem;
use Drupal\Core\Field\Plugin\Field\FieldType\CreatedItem;
use Drupal\Core\Field\Plugin\Field\FieldType\DecimalItem;
use Drupal\Core\Field\Plugin\Field\FieldType\EmailItem;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Field\Plugin\Field\FieldType\FloatItem;
use Drupal\Core\Field\Plugin\Field\FieldType\IntegerItem;
use Drupal\Core\Field\Plugin\Field\FieldType\LanguageItem;
use Drupal\Core\Field\Plugin\Field\FieldType\MapItem;
use Drupal\Core\Field\Plugin\Field\FieldType\PasswordItem;
use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;
use Drupal\Core\Field\Plugin\Field\FieldType\StringLongItem;
use Drupal\Core\Field\Plugin\Field\FieldType\TimestampItem;
use Drupal\Core\Field\Plugin\Field\FieldType\UriItem;
use Drupal\Core\Field\Plugin\Field\FieldType\UuidItem;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\file\Plugin\Field\FieldType\FileItem;
use Drupal\file\Plugin\Field\FieldType\FileUriItem;
use Drupal\link\Plugin\Field\FieldType\LinkItem;
use Drupal\node\Entity\Node;
use Drupal\text\Plugin\Field\FieldType\TextItem;
use Drupal\text\Plugin\Field\FieldType\TextLongItem;
use Drupal\text\Plugin\Field\FieldType\TextWithSummaryItem;
use function PHPStan\Testing\assertType;

$node = Node::create(['type' => 'page']);

// CommentItem.
$comment_field = $node->get('field_comment')->first();
assert($comment_field instanceof CommentItem);
assertType(CommentItem::class, $comment_field);
assertType('int|null', $comment_field->status);
assertType('int|null', $comment_field->cid);
assertType('int|null', $comment_field->last_comment_timestamp);
assertType('string|null', $comment_field->last_comment_name);
assertType('int|null', $comment_field->comment_count);

// LinkItem.
$link_field = $node->get('field_link')->first();
assert($link_field instanceof LinkItem);
assertType(LinkItem::class, $link_field);
assertType('string', $link_field->title);
assertType('string', $link_field->uri);
assertType('array<string, mixed>', $link_field->options);

// BooleanItem.
$boolean_field = $node->get('field_boolean')->first();
assert($boolean_field instanceof BooleanItem);
assertType(BooleanItem::class, $boolean_field);
assertType('int', $boolean_field->value);

// ChangedItem.
$changed_field = $node->get('field_changed')->first();
assert($changed_field instanceof ChangedItem);
assertType(ChangedItem::class, $changed_field);
assertType('string', $changed_field->value);

// CreatedItem.
$created_field = $node->get('field_created')->first();
assert($created_field instanceof CreatedItem);
assertType(CreatedItem::class, $created_field);
assertType('string', $created_field->value);

// DecimalItem.
$decimal_field = $node->get('field_decimal')->first();
assert($decimal_field instanceof DecimalItem);
assertType(DecimalItem::class, $decimal_field);
assertType('string', $decimal_field->value);

// EmailItem.
$email_field = $node->get('field_email')->first();
assert($email_field instanceof EmailItem);
assertType(EmailItem::class, $email_field);
assertType('string', $email_field->value);

// EntityReferenceItem.
$entity_reference_field = $node->get('field_entity_reference')->first();
assert($entity_reference_field instanceof EntityReferenceItem);
assertType(EntityReferenceItem::class, $entity_reference_field);
assertType('int|string|null', $entity_reference_field->target_id);
assertType('Drupal\Core\Entity\EntityInterface|null', $entity_reference_field->entity);

// FloatItem.
$float_field = $node->get('field_float')->first();
assert($float_field instanceof FloatItem);
assertType(FloatItem::class, $float_field);
assertType('float', $float_field->value);

// IntegerItem.
$integer_field = $node->get('field_integer')->first();
assert($integer_field instanceof IntegerItem);
assertType(IntegerItem::class, $integer_field);
assertType('int', $integer_field->value);

// LanguageItem.
$language_field = $node->get('field_language')->first();
assert($language_field instanceof LanguageItem);
assertType(LanguageItem::class, $language_field);
assertType('string', $language_field->value);
assertType('Drupal\Core\Language\LanguageInterface', $language_field->language);

// MapItem.
$map_field = $node->get('field_map')->first();
assert($map_field instanceof MapItem);
assertType(MapItem::class, $map_field);
assertType('array<string, mixed>', $map_field->value);

// PasswordItem.
$password_field = $node->get('field_password')->first();
assert($password_field instanceof PasswordItem);
assertType(PasswordItem::class, $password_field);
assertType('string', $password_field->value);
assertType('string|null', $password_field->existing);
assertType('bool', $password_field->pre_hashed);

// StringItem.
$string_field = $node->get('field_string')->first();
assert($string_field instanceof StringItem);
assertType(StringItem::class, $string_field);
assertType('string', $string_field->value);

// StringItemLong.
$string_long_field = $node->get('field_string_long')->first();
assert($string_long_field instanceof StringLongItem);
assertType(StringLongItem::class, $string_long_field);
assertType('string', $string_long_field->value);

// TimestampItem.
$timestamp_field = $node->get('field_timestamp')->first();
assert($timestamp_field instanceof TimestampItem);
assertType(TimestampItem::class, $timestamp_field);
assertType('string', $timestamp_field->value);

// UriItem.
$uri_field = $node->get('field_uri')->first();
assert($uri_field instanceof UriItem);
assertType(UriItem::class, $uri_field);
assertType('string', $uri_field->value);

// UuidItem.
$uuid_field = $node->get('field_uuid')->first();
assert($uuid_field instanceof UuidItem);
assertType(UuidItem::class, $uuid_field);
assertType('string', $uuid_field->value);

// DateTimeItem.
$datetime_field = $node->get('field_datetime')->first();
assert($datetime_field instanceof DateTimeItem);
assertType(DateTimeItem::class, $datetime_field);
assertType('string|null', $datetime_field->value);
assertType('Drupal\Core\Datetime\DrupalDateTime|null', $datetime_field->date);

// FileItem.
$file_field = $node->get('field_file')->first();
assert($file_field instanceof FileItem);
assertType(FileItem::class, $file_field);
assertType('bool', $file_field->display);
assertType('string', $file_field->description);

// FileUriItem.
$file_uri_field = $node->get('field_file')->first();
assert($file_uri_field instanceof FileUriItem);
assertType(FileUriItem::class, $file_uri_field);
assertType('string', $file_uri_field->url);

// TextITem.
$text_field = $node->get('field_text')->first();
assert($text_field instanceof TextItem);
assertType(TextItem::class, $text_field);
assertType('string|null', $text_field->value);
assertType('string|null', $text_field->format);
assertType('Drupal\Component\Render\MarkupInterface', $text_field->processed);

// TextLongITem.
$text_long_field = $node->get('field_text_long')->first();
assert($text_long_field instanceof TextLongItem);
assertType(TextLongItem::class, $text_long_field);
assertType('string|null', $text_long_field->value);
assertType('string|null', $text_long_field->format);
assertType('Drupal\Component\Render\MarkupInterface', $text_long_field->processed);

// TextWithSummaryITem.
$text_with_summary_field = $node->get('field_text_with_summary')->first();
assert($text_with_summary_field instanceof TextWithSummaryItem);
assertType(TextWithSummaryItem::class, $text_with_summary_field);
assertType('string|null', $text_with_summary_field->value);
assertType('string|null', $text_with_summary_field->format);
assertType('string|null', $text_with_summary_field->summary);
assertType('Drupal\Component\Render\MarkupInterface', $text_with_summary_field->summary_processed);
