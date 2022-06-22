<?php
declare(strict_types=1);

namespace Fyre\ORM;

use
    Fyre\ORM\Exceptions\OrmException;

use function
    class_exists,
    in_array,
    is_subclass_of,
    trim;

abstract class BehaviorRegistry
{

    protected static array $namespaces = [];

    protected static array $behaviors = [];

    /**
     * Add a namespace for loading behaviors.
     * @param string $namespace The namespace.
     */
    public static function addNamespace(string $namespace): void
    {
        $namespace = static::normalizeNamespace($namespace);

        if (!in_array($namespace, static::$namespaces)) {
            static::$namespaces[] = $namespace;
        }
    }

    /**
     * Clear all namespaces and behaviors.
     */
    public static function clear(): void
    {
        static::$namespaces = [];
        static::$behaviors = [];
    }

    /**
     * Find a behavior class.
     * @param string $name The behavior name.
     * @return string|null The behavior class.
     */
    public static function find(string $name): string|null
    {
        return static::$behaviors[$name] ??= static::locate($name);
    }

    /**
     * Load a behavior.
     * @param string $name The behavior name.
     * @param Model $model The Model.
     * @param array $options The behavior options.
     * @return Behavior The Behavior.
     * @throws OrmException if the behavior does not exist.
     */
    public static function load(string $name, Model $model, array $options = []): Behavior
    {
        $className = static::find($name);

        if (!$className) {
            throw OrmException::forInvalidBehavior($name);
        }

        return new $className($model, $options);
    }

    /**
     * Locate a behavior class.
     * @param string $name The behavior name.
     * @return string|null The behavior class.
     */
    protected static function locate(string $name): string|null
    {
        $namespaces = array_merge(static::$namespaces, ['\Fyre\ORM\Behaviors\\']);

        foreach ($namespaces AS $namespace) {
            $className = $namespace.$name;

            if (class_exists($className) && is_subclass_of($className, Behavior::class)) {
                return $className;
            }
        }

        return null;
    }

    /**
     * Normalize a namespace
     * @param string $namespace The namespace.
     * @return string The normalized namespace.
     */
    protected static function normalizeNamespace(string $namespace): string
    {
        $namespace = trim($namespace, '\\');

        return $namespace ?
            '\\'.$namespace.'\\' :
            '\\';
    }

}
