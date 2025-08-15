<?php

namespace Clickfwd\Yoyo;

use ReflectionClass;
use ReflectionMethod;

class ClassHelpers
{
    public static function getDefaultPublicVars($instance, $baseClass = null)
    {
        $class = new ReflectionClass(get_class($instance));

        $names = self::getPublicProperties($instance, $baseClass);

        $values = $class->getDefaultProperties();

        return array_intersect_key($values, array_flip($names));
    }

    public static function getPublicVars($instance, $baseClass = null)
    {
        $publicProperties = self::getPublicProperties($instance, $baseClass);

        $vars = call_user_func('get_object_vars', $instance);

        $publicVars = [];

        foreach ($vars as $key => $value) {
            if (in_array($key, $publicProperties)) {
                $publicVars[$key] = $vars[$key];
            }
        }

        return $publicVars;
    }

    public static function getPublicProperties($instance, $baseClass = null)
    {
        $class = new ReflectionClass(get_class($instance));

        $className = $class->getName();

        $properties = $class->getProperties(ReflectionMethod::IS_PUBLIC);

        $publicProperties = [];

        foreach ($properties as $prop) {
            // Only include the property if it's different from the base class when passed as 2d parameter
            // This allows extending component classes with public properties
            if ($baseClass && $prop->class !== $baseClass || $prop->class == $className) {
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
            if (! $parameter->getType() || ($parameter->getType() && $parameter->getType()->isBuiltin())) {
                $names[] = $parameter->getName();
            }
        }

        return $names;
    }
    
    public static function methodHasVariadicParameter($class, $method)
    {
        $reflector = new ReflectionClass($class);
        $method = $reflector->getMethod($method);
        $parameters = $method->getParameters();
        
        if (empty($parameters)) {
            return false;
        }
        
        // Check if the last parameter is variadic
        $lastParam = end($parameters);
        return $lastParam->isVariadic();
    }
    
    /**
     * Get all method parameters with type information
     * Returns an array with 'typed' and 'regular' parameters
     */
    public static function getMethodParametersWithTypes($class, $method)
    {
        $typed = [];    // Parameters with class type hints (for DI)
        $regular = [];  // Parameters without type hints or with builtin types
        
        $reflector = new ReflectionClass($class);
        $method = $reflector->getMethod($method);
        
        foreach ($method->getParameters() as $parameter) {
            $paramInfo = [
                'name' => $parameter->getName(),
                'optional' => $parameter->isOptional(),
                'variadic' => $parameter->isVariadic(),
            ];
            
            if (! $parameter->getType() || ($parameter->getType() && $parameter->getType()->isBuiltin())) {
                // Regular parameter (no type or builtin type)
                $regular[] = $paramInfo;
            } else {
                // Typed parameter (class type hint for DI)
                $paramInfo['type'] = $parameter->getType()->getName();
                $typed[] = $paramInfo;
            }
        }
        
        return [
            'typed' => $typed,
            'regular' => $regular,
        ];
    }
}
