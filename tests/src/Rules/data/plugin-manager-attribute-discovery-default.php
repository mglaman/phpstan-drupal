<?php

namespace PluginManagerAttributeDiscoveryDefault;

use Drupal\Component\Annotation\Plugin as AnnotationPlugin;
use Drupal\Component\Plugin\Attribute\Plugin as AttributePlugin;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

// Positive: static property defaults to an annotation class.
class StaticPropertyAnnotation extends DefaultPluginManager
{

    protected static $annotation = 'Drupal\Component\Annotation\Plugin';

    public function __construct(\Traversable $namespaces, ModuleHandlerInterface $module_handler)
    {
        parent::__construct('Plugin/Foo', $namespaces, $module_handler, 'FooInterface', static::$annotation);
    }

}

// Positive: self:: and typed property with ::class default.
class SelfPropertyAnnotation extends DefaultPluginManager
{

    protected static string $annotation = AnnotationPlugin::class;

    public function __construct(\Traversable $namespaces, ModuleHandlerInterface $module_handler)
    {
        parent::__construct('Plugin/Foo', $namespaces, $module_handler, 'FooInterface', self::$annotation);
    }

}

// Positive: the nearest declaration wins, the subclass redeclares the default.
abstract class AttributeBase extends DefaultPluginManager
{

    protected static $annotation = 'Drupal\Component\Plugin\Attribute\Plugin';

}

class RedeclaredToAnnotation extends AttributeBase
{

    protected static $annotation = 'Drupal\Component\Annotation\Plugin';

    public function __construct(\Traversable $namespaces, ModuleHandlerInterface $module_handler)
    {
        parent::__construct('Plugin/Foo', $namespaces, $module_handler, 'FooInterface', static::$annotation);
    }

}

// Negative: static property defaults to an attribute class.
class StaticPropertyAttribute extends DefaultPluginManager
{

    protected static $annotation = AttributePlugin::class;

    public function __construct(\Traversable $namespaces, ModuleHandlerInterface $module_handler)
    {
        parent::__construct('Plugin/Foo', $namespaces, $module_handler, 'FooInterface', static::$annotation);
    }

}

// Negative: property is reassigned in the class.
class StaticPropertyReassigned extends DefaultPluginManager
{

    protected static $annotation = 'Drupal\Component\Annotation\Plugin';

    public function __construct(\Traversable $namespaces, ModuleHandlerInterface $module_handler)
    {
        static::$annotation = AttributePlugin::class;
        parent::__construct('Plugin/Foo', $namespaces, $module_handler, 'FooInterface', static::$annotation);
    }

}

// Negative: static property without a default.
class StaticPropertyNoDefault extends DefaultPluginManager
{

    protected static $annotation;

    public function __construct(\Traversable $namespaces, ModuleHandlerInterface $module_handler)
    {
        parent::__construct('Plugin/Foo', $namespaces, $module_handler, 'FooInterface', static::$annotation);
    }

}

// Negative: default is not resolvable.
class StaticPropertyUnresolvable extends DefaultPluginManager
{

    protected static $annotation = 'Not\Existing\Annotation';

    public function __construct(\Traversable $namespaces, ModuleHandlerInterface $module_handler)
    {
        parent::__construct('Plugin/Foo', $namespaces, $module_handler, 'FooInterface', static::$annotation);
    }

}

// Negative: not a static property.
class InstanceProperty extends DefaultPluginManager
{

    protected $annotation = 'Drupal\Component\Annotation\Plugin';

    public function __construct(\Traversable $namespaces, ModuleHandlerInterface $module_handler)
    {
        parent::__construct('Plugin/Foo', $namespaces, $module_handler, 'FooInterface', $this->annotation);
    }

}

// Positive: constructor parameter defaults to an annotation class.
class ParameterDefaultAnnotation extends DefaultPluginManager
{

    public function __construct(\Traversable $namespaces, ModuleHandlerInterface $module_handler, $plugin_interface = null, $plugin_definition_annotation_name = 'Drupal\Component\Annotation\Plugin')
    {
        parent::__construct('Plugin/Foo', $namespaces, $module_handler, $plugin_interface, $plugin_definition_annotation_name);
    }

}

// Positive: ::class default and named argument.
class ParameterDefaultClassConstNamed extends DefaultPluginManager
{

    public function __construct(\Traversable $namespaces, ModuleHandlerInterface $module_handler, string $annotation = AnnotationPlugin::class)
    {
        parent::__construct('Plugin/Foo', $namespaces, $module_handler, plugin_definition_attribute_name: $annotation);
    }

}

// Negative: parameter defaults to an attribute class.
class ParameterDefaultAttribute extends DefaultPluginManager
{

    public function __construct(\Traversable $namespaces, ModuleHandlerInterface $module_handler, $attribute = AttributePlugin::class)
    {
        parent::__construct('Plugin/Foo', $namespaces, $module_handler, 'FooInterface', $attribute);
    }

}

// Negative: parameter is reassigned.
class ParameterReassigned extends DefaultPluginManager
{

    public function __construct(\Traversable $namespaces, ModuleHandlerInterface $module_handler, $annotation = 'Drupal\Component\Annotation\Plugin')
    {
        $annotation = AttributePlugin::class;
        parent::__construct('Plugin/Foo', $namespaces, $module_handler, 'FooInterface', $annotation);
    }

}

// Negative: parameter without a default.
class ParameterNoDefault extends DefaultPluginManager
{

    public function __construct(\Traversable $namespaces, ModuleHandlerInterface $module_handler, $annotation)
    {
        parent::__construct('Plugin/Foo', $namespaces, $module_handler, 'FooInterface', $annotation);
    }

}

// Negative: null default.
class ParameterNullDefault extends DefaultPluginManager
{

    public function __construct(\Traversable $namespaces, ModuleHandlerInterface $module_handler, ?string $annotation = null)
    {
        parent::__construct('Plugin/Foo', $namespaces, $module_handler, 'FooInterface', $annotation);
    }

}

// Negative: not a plugin manager.
class NotAPluginManager
{

    protected static $annotation = 'Drupal\Component\Annotation\Plugin';

    public function __construct()
    {
        parent::__construct(static::$annotation);
    }

}
