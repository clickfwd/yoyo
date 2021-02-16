<?php

namespace Clickfwd\Yoyo;

use ReflectionClass;
use ReflectionParameter;
use Exception;

class DI
{

    /**
     * Resolve class method dependencies with class dependency injection and
     * automatic mapping of parameter names with variable names when a matching key is found
    *
    * @param mixed $class
    * @return mixed
    *
    * @throws Exception
    */
    public static function call($class, array $variables, string $defaultMethod = null)
    {
        $reflector = new ReflectionClass($class);

        if (! $reflector->isInstantiable()) {
            throw new Exception("[$class] is not instantiable");
        }
        
        if (! $defaultMethod) {
            $method = $reflector->getConstructor();
        } else {
            $method = $reflector->getMethod($defaultMethod);
        }
    
        if (is_null($method)) {
            return new $class;
        }
    
        $parameters = $method->getParameters();

        $dependencies = static::getDependencies($parameters, $class, $variables, $defaultMethod);
        
        // Automatically include all unnamed variables as arguments
        $unnamed = array_filter($variables, function ($value, $key) {
            return is_numeric($key);
        }, ARRAY_FILTER_USE_BOTH);

        $dependencies = array_merge($unnamed, $dependencies);
        
        if (is_string($class)) {
            if (! $defaultMethod) {
                return $reflector->newInstanceArgs($dependencies);
            } else {
                $instance = new $class();
                return $instance->{$defaultMethod}(...$dependencies);
            }
        }

        return static::expose($class, $defaultMethod, $dependencies);
    }

    /**
     * Allows running protected methods externally
     */
    protected static function expose($object, $method, array $args = [])
    {
        $fn = function () use ($object, $method, $args) {
            return $object->$method(...$args);
        };
        
        $exposed = $fn->bindTo($object, $object);
        
        return $exposed();
    }

    /**
     * Recursively build list of dependencies for class method
     *
     * @param array $parameters
     * @return array
     */
    protected static function getDependencies($parameters, $class, $variables, $defaultMethod)
    {
        $dependencies = [];
    
        foreach ($parameters as $parameter) {
            if ($dependency = $parameter->getClass()) {
                $dependencies[] = static::call($dependency->name, $variables);
            } elseif (isset($variables[$parameter->name])) {
                $dependencies[] = $variables[$parameter->name];
            } elseif ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
            } else {
                // static::resolveNonClass($parameter, $class, $defaultMethod);
            }
        }
    
        return $dependencies;
    }

    /**
     * Deal with non-class parameters
     *
     * @param ReflectionParameter $parameter
     * @return mixed
     *
     * @throws Exception
     */
    protected static function resolveNonClass(ReflectionParameter $parameter, $class, $defaultMethod)
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        $defaultMethod = $defaultMethod ?? '__construct';
    
        throw new Exception("Cannot resolve \"\${$parameter->getName()}\" parameter for \"{$class}::{$defaultMethod}\".");
    }
}
