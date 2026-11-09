<?php

namespace PluginManagerAttributeDiscovery;

use Drupal\Component\Annotation\Plugin as AnnotationPlugin;
use Drupal\Component\Plugin\Attribute\Plugin as AttributePlugin;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

class AnnotationOnly extends DefaultPluginManager
{

    public function __construct(\Traversable $namespaces, ModuleHandlerInterface $module_handler)
    {
        parent::__construct('Plugin/Foo', $namespaces, $module_handler, 'FooInterface', AnnotationPlugin::class);
    }

}

class AnnotationOnlyAsString extends DefaultPluginManager
{

    public function __construct(\Traversable $namespaces, ModuleHandlerInterface $module_handler)
    {
        parent::__construct('Plugin/Foo', $namespaces, $module_handler, 'FooInterface', '\Drupal\Component\Annotation\Plugin');
    }

}

class AnnotationOnlyNamedArgs extends DefaultPluginManager
{

    public function __construct(\Traversable $namespaces, ModuleHandlerInterface $module_handler)
    {
        parent::__construct(
            subdir: 'Plugin/Foo',
            namespaces: $namespaces,
            module_handler: $module_handler,
            plugin_definition_attribute_name: AnnotationPlugin::class,
        );
    }

}

class AttributeOnly extends DefaultPluginManager
{

    public function __construct(\Traversable $namespaces, ModuleHandlerInterface $module_handler)
    {
        parent::__construct('Plugin/Foo', $namespaces, $module_handler, 'FooInterface', AttributePlugin::class);
    }

}

class AttributeAndAnnotation extends DefaultPluginManager
{

    public function __construct(\Traversable $namespaces, ModuleHandlerInterface $module_handler)
    {
        parent::__construct('Plugin/Foo', $namespaces, $module_handler, 'FooInterface', AttributePlugin::class, AnnotationPlugin::class);
    }

}

class AttributeNamedArgs extends DefaultPluginManager
{

    public function __construct(\Traversable $namespaces, ModuleHandlerInterface $module_handler)
    {
        parent::__construct(
            'Plugin/Foo',
            $namespaces,
            $module_handler,
            plugin_definition_attribute_name: AttributePlugin::class,
            plugin_definition_annotation_name: AnnotationPlugin::class,
        );
    }

}

// Omitting the argument does not trigger a deprecation in core.
class ArgumentOmitted extends DefaultPluginManager
{

    public function __construct(\Traversable $namespaces, ModuleHandlerInterface $module_handler)
    {
        parent::__construct('Plugin/Foo', $namespaces, $module_handler, 'FooInterface');
    }

}

// Unresolvable class: cannot be sure, so it is not reported.
class UnresolvableClass extends DefaultPluginManager
{

    public function __construct(\Traversable $namespaces, ModuleHandlerInterface $module_handler)
    {
        parent::__construct('Plugin/Foo', $namespaces, $module_handler, 'FooInterface', 'Not\Existing\Annotation');
    }

}

// Dynamic value: type is not a constant string.
class DynamicArgument extends DefaultPluginManager
{

    public function __construct(\Traversable $namespaces, ModuleHandlerInterface $module_handler, string $attribute)
    {
        parent::__construct('Plugin/Foo', $namespaces, $module_handler, 'FooInterface', $attribute);
    }

}

// Not a plugin manager.
class NotAPluginManager
{

    public function __construct()
    {
    }

}

class NotAPluginManagerChild extends NotAPluginManager
{

    public function __construct()
    {
        parent::__construct(AnnotationPlugin::class, AnnotationPlugin::class, AnnotationPlugin::class, AnnotationPlugin::class, AnnotationPlugin::class);
    }

}

// Intermediate parent with its own constructor signature.
class IntermediateManager extends DefaultPluginManager
{

    public function __construct(\Traversable $namespaces, ModuleHandlerInterface $module_handler)
    {
        parent::__construct('Plugin/Foo', $namespaces, $module_handler, 'FooInterface', AttributePlugin::class);
    }

}

class ChildOfIntermediate extends IntermediateManager
{

    public function __construct(\Traversable $namespaces, ModuleHandlerInterface $module_handler)
    {
        parent::__construct($namespaces, $module_handler);
    }

}
