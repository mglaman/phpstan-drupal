<?php

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterInterface;
use Drupal\file\Plugin\Field\FieldType\FileFieldItemList;
use function PHPStan\Testing\assertType;

class FooFormatter implements FormatterInterface
{

    public function prepareView(array $entities_items)
    {
        assertType('array<Drupal\Core\Field\FieldItemListInterface<Drupal\Core\Field\FieldItemInterface>>', $entities_items);
    }

    public function view(FieldItemListInterface $items, $langcode = NULL)
    {
        assertType('Drupal\Core\Field\FieldItemListInterface<Drupal\Core\Field\FieldItemInterface>', $items);
    }

    public function viewElements(FieldItemListInterface $items, $langcode)
    {
        assertType('Drupal\Core\Field\FieldItemListInterface<Drupal\Core\Field\FieldItemInterface>', $items);
    }

}

/**
 * @implements FormatterInterface<\Drupal\Core\Field\Plugin\Field\FieldType\StringItem>
 */
class BarFormatter implements FormatterInterface
{

    public function prepareView(array $entities_items)
    {
        assertType('array<Drupal\Core\Field\FieldItemListInterface<Drupal\Core\Field\Plugin\Field\FieldType\StringItem>>', $entities_items);
    }

    public function view(FieldItemListInterface $items, $langcode = NULL)
    {
        assertType('Drupal\Core\Field\FieldItemListInterface<Drupal\Core\Field\Plugin\Field\FieldType\StringItem>', $items);
    }

    public function viewElements(FieldItemListInterface $items, $langcode)
    {
        assertType('Drupal\Core\Field\FieldItemListInterface<Drupal\Core\Field\Plugin\Field\FieldType\StringItem>', $items);
    }

}

/**
 * @implements FormatterInterface<FileFieldItemList<\Drupal\file\Plugin\Field\FieldType\FileItem>
 */
class BazFormatter implements FormatterInterface
{

    public function prepareView(array $entities_items)
    {
        assertType('array<Drupal\file\Plugin\Field\FieldType\FileFieldItemList<Drupal\file\Plugin\Field\FieldType\FileItem>>', $entities_items);
    }

    public function view(FieldItemListInterface $items, $langcode = NULL)
    {
        assertType('Drupal\file\Plugin\Field\FieldType\FileFieldItemList<Drupal\file\Plugin\Field\FieldType\FileItem>', $items);
    }

    public function viewElements(FieldItemListInterface $items, $langcode)
    {
        assertType('Drupal\file\Plugin\Field\FieldType\FileFieldItemList<Drupal\file\Plugin\Field\FieldType\FileItem>', $items);
    }

}
