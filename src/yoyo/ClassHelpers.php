<?php

namespace Clickfwd\Yoyo;

use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

class ClassHelpers
{
    private static array $propertyCache = [];

    private static array $defaultVarCache = [];

    private static array $methodCache = [];

    private static array $traitCache = [];

    private static array $paramTypeCache = [];

    private static array $objectPropertyCache = [];

    public static function getDefaultPublicVars($instance, $baseClass = null)
    {
        $className = get_class($instance);
        $cacheKey = $className . ':' . ($baseClass ?? '');

        if (isset(static::$defaultVarCache[$cacheKey])) {
            return static::$defaultVarCache[$cacheKey];
        }

        $class = new ReflectionClass($className);

        $names = self::getPublicProperties($instance, $baseClass);

        $values = $class->getDefaultProperties();

        return static::$defaultVarCache[$cacheKey] = array_intersect_key($values, array_flip($names));
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

    /**
     * Public properties declared to hold an object.
     *
     * These name a collaborator rather than a value, so a request variable sharing the
     * name cannot be what the property is for -- assigning one is a fatal at best, and
     * a silently wrong collaborator at worst.
     */
    public static function getObjectTypedProperties($instance, $baseClass = null)
    {
        $className = get_class($instance);
        $cacheKey = $className.':'.($baseClass ?? '');

        if (isset(static::$objectPropertyCache[$cacheKey])) {
            return static::$objectPropertyCache[$cacheKey];
        }

        $class = new ReflectionClass($className);

        $objectProperties = [];

        foreach (static::getPublicProperties($instance, $baseClass) as $name) {
            $type = $class->getProperty($name)->getType();

            if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
                $objectProperties[] = $name;
            }
        }

        return static::$objectPropertyCache[$cacheKey] = $objectProperties;
    }

    public static function getPublicProperties($instance, $baseClass = null)
    {
        $className = get_class($instance);
        $cacheKey = $className . ':' . ($baseClass ?? '');

        if (isset(static::$propertyCache[$cacheKey])) {
            return static::$propertyCache[$cacheKey];
        }

        $class = new ReflectionClass($className);

        $properties = $class->getProperties(ReflectionMethod::IS_PUBLIC);

        $publicProperties = [];

        foreach ($properties as $prop) {
            // Only include the property if it's different from the base class when passed as 2d parameter
            // This allows extending component classes with public properties
            if (($baseClass && $prop->class !== $baseClass) || $prop->class == $className) {
                $publicProperties[] = $prop->name;
            }
        }

        return static::$propertyCache[$cacheKey] = $publicProperties;
    }

    public static function getPublicMethods($instance, $exceptions = [])
    {
        $className = is_string($instance) ? $instance : get_class($instance);
        $cacheKey = $className . ':' . implode(',', $exceptions);

        if (isset(static::$methodCache[$cacheKey])) {
            return static::$methodCache[$cacheKey];
        }

        $class = new ReflectionClass($className);

        $methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if ($method->class == $className && ! in_array($method->name, $exceptions)) {
                $publicMethods[] = $method->name;
            }
        }

        return static::$methodCache[$cacheKey] = $publicMethods ?? [];
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

        $className = $class;

        if (isset(static::$traitCache[$className])) {
            return static::$traitCache[$className];
        }

        $results = [];

        foreach (array_reverse(class_parents($class)) + [$class => $class] as $class) {
            $results += static::traitUsesRecursive($class);
        }

        return static::$traitCache[$className] = array_unique($results);
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
            if (! static::isContainerResolvedParameter($parameter)) {
                $names[] = $parameter->getName();
            }
        }

        return $names;
    }

    /**
     * Whether a parameter is one the container should resolve, rather than one a
     * caller supplies a value for.
     *
     * Mirrors the container's own rule: only a single named class or interface type
     * qualifies. Union and intersection types report as ReflectionUnionType and
     * ReflectionIntersectionType, neither of which has isBuiltin(), so they must be
     * matched by instance rather than interrogated.
     */
    private static function isContainerResolvedParameter(ReflectionParameter $parameter): bool
    {
        $type = $parameter->getType();

        return $type instanceof ReflectionNamedType && ! $type->isBuiltin();
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
        $className = is_object($class) ? get_class($class) : $class;
        $cacheKey = $className . ':' . $method;

        if (isset(static::$paramTypeCache[$cacheKey])) {
            return static::$paramTypeCache[$cacheKey];
        }

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

            if (! static::isContainerResolvedParameter($parameter)) {
                // Regular parameter (no type, builtin type, or composite type)
                $regular[] = $paramInfo;
            } else {
                // Typed parameter (class type hint for DI)
                $paramInfo['type'] = $parameter->getType()->getName();
                $typed[] = $paramInfo;
            }
        }

        return static::$paramTypeCache[$cacheKey] = [
            'typed' => $typed,
            'regular' => $regular,
        ];
    }

    public static function flushCache(): void
    {
        static::$propertyCache = [];
        static::$defaultVarCache = [];
        static::$methodCache = [];
        static::$traitCache = [];
        static::$paramTypeCache = [];
        static::$objectPropertyCache = [];
    }
}
