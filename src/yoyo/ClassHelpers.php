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
        $class = new ReflectionClass(is_string($instance) ? $instance : get_class($instance));

        $className = $class->getName();

        $methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if ($method->class == $className && ! in_array($method->name, $exceptions)) {
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

    /**
     * Laravel Support helper
     */
    public static function classUsesRecursive($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        $results = [];

        foreach (array_reverse(class_parents($class)) + [$class => $class] as $class) {
            $results += static::traitUsesRecursive($class);
        }

        return array_unique($results);
    }

    /**
     * Laravel Support helper
     */
    public static function traitUsesRecursive($trait)
    {
        $traits = class_uses($trait);

        foreach ($traits as $trait) {
            $traits += static::traitUsesRecursive($trait);
        }

        return $traits;
    }

    /**
     * Laravel Support helper
     */
    public static function classBasename($class)
    {
        $class = is_object($class) ? get_class($class) : $class;

        return basename(str_replace('\\', '/', $class));
    }

    public static function getMethodParameterNames($class, $method)
    {
        $names = [];

        $reflector = new ReflectionClass($class);

        $method = $reflector->getMethod($method);

        foreach ($method->getParameters() as $parameter) {
            if (! $parameter->getType() || ($parameter->getType() && ! $parameter->getType()->getName())) {
                $names[] = $parameter->getName();
            }
        }

        return $names;
    }
}
