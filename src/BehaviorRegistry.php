<?php
declare(strict_types=1);

namespace Fyre\ORM;

use Fyre\ORM\Exceptions\OrmException;

use function class_exists;
use function in_array;
use function is_subclass_of;
use function trim;

abstract class BehaviorRegistry
{
    protected static array $behaviors = [];

    protected static array $namespaces = [];

    /**
     * Add a namespace for loading behaviors.
     *
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
     *
     * @param string $name The behavior name.
     * @return string|null The behavior class.
     */
    public static function find(string $name): string|null
    {
        return static::$behaviors[$name] ??= static::locate($name);
    }

    /**
     * Get the namespaces.
     *
     * @return array The namespaces.
     */
    public static function getNamespaces(): array
    {
        return static::$namespaces;
    }

    /**
     * Determine if a namespace exists.
     *
     * @param string $namespace The namespace.
     * @return bool TRUE if the namespace exists, otherwise FALSE.
     */
    public static function hasNamespace(string $namespace): bool
    {
        $namespace = static::normalizeNamespace($namespace);

        return in_array($namespace, static::$namespaces);
    }

    /**
     * Load a behavior.
     *
     * @param string $name The behavior name.
     * @param Model $model The Model.
     * @param array $options The behavior options.
     * @return Behavior The Behavior.
     *
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
     * Remove a namespace.
     *
     * @param string $namespace The namespace.
     * @return bool TRUE If the namespace was removed, otherwise FALSE.
     */
    public static function removeNamespace(string $namespace): bool
    {
        $namespace = static::normalizeNamespace($namespace);

        foreach (static::$namespaces as $i => $otherNamespace) {
            if ($otherNamespace !== $namespace) {
                continue;
            }

            array_splice(static::$namespaces, $i, 1);

            return true;
        }

        return false;
    }

    /**
     * Locate a behavior class.
     *
     * @param string $name The behavior name.
     * @return string|null The behavior class.
     */
    protected static function locate(string $name): string|null
    {
        $namespaces = array_merge(static::$namespaces, ['\Fyre\ORM\Behaviors\\']);

        foreach ($namespaces as $namespace) {
            $className = $namespace.$name.'Behavior';

            if (class_exists($className) && is_subclass_of($className, Behavior::class)) {
                return $className;
            }
        }

        return null;
    }

    /**
     * Normalize a namespace
     *
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
