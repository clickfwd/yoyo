<?php

namespace Clickfwd\Yoyo;

use ReflectionClass;
use ReflectionMethod;

class ClassHelpers
{
    public static function getDefaultPublicVars($instance)
    {
        $class = new ReflectionClass(get_class($instance));

        $names = self::getPublicProperties($instance);

        $values = $class->getDefaultProperties();

        return array_intersect_key($values, array_flip($names));
    }

    public static function getPublicVars($instance)
    {
        $publicProperties = self::getPublicProperties($instance);

        $vars = call_user_func('get_object_vars', $instance);

        $publicVars = [];

        foreach ($vars as $key => $value) {
            if (in_array($key, $publicProperties)) {
                $publicVars[$key] = $vars[$key];
            }
        }

        return $publicVars;
    }

    public static function getPublicProperties($instance)
    {
        $class = new ReflectionClass(get_class($instance));

        $className = $class->getName();

        $properties = $class->getProperties(ReflectionMethod::IS_PUBLIC);

        $publicProperties = [];

        foreach ($properties as $prop) {
            if ($prop->class == $className) {
                $publicProperties[] = $prop->name;
            }
        }

        return $publicProperties;
    }

    public static function getPublicMethods($instance, $exceptions = [])
    {
        $class = new ReflectionClass(get_class($instance));

        $className = $class->getName();

        $methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if ($method->class == $className && ! in_array($method->name, $exceptions)) {
                $publicMethods[] = $method->name;
            }
        }

        return $publicMethods ?? [];
    }

    public static function getPublicMethodsBaseClass($instance, $exceptions = [])
    {
        $class_tmp = new ReflectionClass(get_class($instance));

        $className = $class_tmp->getName();

        $methods = $class_tmp->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if ($method->class !== $className && ! in_array($method->name, $exceptions)) {
                $publicMethods[] = $method->name;
            }
        }

        return $publicMethods ?? [];
    }

    public static function methodIsPrivate($instance, $method)
    {
        $reflection = new ReflectionMethod($instance, $method);

        return ! $reflection->isPublic();
    }

    public static function classImplementsInterface($name, $instance)
    {
        $class = new ReflectionClass($name);

        return in_array($instance, $class->getInterfaceNames());
    }
}
