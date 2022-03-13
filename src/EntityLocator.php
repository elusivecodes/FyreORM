<?php
declare(strict_types=1);

namespace Fyre\ORM;

use
    Fyre\Entity\Entity,
    Fyre\Utility\Inflector;

use function
    class_exists,
    in_array,
    is_subclass_of,
    trim;

/**
 * EntityLocator
 */
abstract class EntityLocator
{

    protected static string $defaultEntityClass = Entity::class;

    protected static array $namespaces = [];

    protected static array $entities = [];

    /**
     * Add a namespace for locating entities.
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
     * Clear all namespaces and entities.
     */
    public static function clear(): void
    {
        static::$namespaces = [];
        static::$entities = [];
    }

    /**
     * Get the default entity class name.
     * @return string The default entity class name.
     */
    public static function getDefaultEntityClass(): string
    {
        return static::$defaultEntityClass;
    }

    /**
     * Find the entity class name for an alias.
     * @param string $alias The alias.
     * @return string The entity class name.
     */
    public static function find(string $alias): string
    {
        return static::$entities[$alias] ??= static::locate($alias);
    }

    /**
     * Set the default entity class name.
     * @param string $defaultEntityClass The default entity class name.
     */
    public static function setDefaultEntityClass(string $defaultEntityClass): void
    {
        static::$defaultEntityClass = $defaultEntityClass;
    }

    /**
     * Locate the entity class name for an alias.
     * @param string $alias The alias.
     * @return string The entity class name.
     */
    protected static function locate(string $alias): string
    {
        $alias = Inflector::singularize($alias);

        foreach (static::$namespaces AS $namespace) {
            $fullClass = $namespace.$alias;

            if (class_exists($fullClass) && is_subclass_of($fullClass, Entity::class)) {
                return $fullClass;
            }
        }

        return static::$defaultEntityClass;
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
