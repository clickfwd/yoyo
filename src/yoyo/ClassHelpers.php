<?php

namespace Clickfwd\Yoyo;

use ReflectionClass;
use ReflectionMethod;

class ClassHelpers
{
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

        $properties = $class->getProperties(ReflectionMethod::IS_PUBLIC);

        $publicProperties = [];

        foreach ($properties as $row) {
            $publicProperties[] = $row->name;
        }

        return $publicProperties;
    }

    public static function getPublicMethods($instance, $exceptions = [])
    {
        $class_tmp = new ReflectionClass(get_class($instance));

        $className = $class_tmp->getName();

        $methods = $class_tmp->getMethods(ReflectionMethod::IS_PUBLIC);

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
}
