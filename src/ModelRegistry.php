<?php
declare(strict_types=1);

namespace Fyre\ORM;

use function
    class_exists,
    in_array,
    is_subclass_of,
    trim;

/**
 * ModelRegistry
 */
abstract class ModelRegistry
{

    protected static string $defaultModelClass = Model::class;

    protected static array $namespaces = [];

    protected static array $instances = [];

    /**
     * Add a namespace for loading models.
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
     * Clear all namespaces and models.
     */
    public static function clear(): void
    {
        static::$namespaces = [];
        static::$instances = [];
    }

    /**
     * Create a default Model.
     * @return Model The Model.
     */
    public static function createDefaultModel(): Model
    {
        $modelClass = static::$defaultModelClass;

        return new $modelClass;
    }

    /**
     * Get the default model class name.
     * @return string The default model class name.
     */
    public static function getDefaultModelClass(): string
    {
        return static::$defaultModelClass;
    }

    /**
     * Load a Model.
     * @param string $alias The model alias.
     * @return Model The Model.
     */
    public static function load(string $alias): Model
    {
        foreach (static::$namespaces AS $namespace) {
            $fullClass = $namespace.$alias;

            if (class_exists($fullClass) && is_subclass_of($fullClass, Model::class)) {
                return new $fullClass;
            }
        }

        return static::createDefaultModel()->setAlias($alias);
    }

    /**
     * Set the default model class name.
     * @param string $defaultModelClass The default model class name.
     */
    public static function setDefaultModelClass(string $defaultModelClass): void
    {
        static::$defaultModelClass = $defaultModelClass;
    }

    /**
     * Load a shared Model instance.
     * @param string $alias The model alias.
     * @return Model The Model.
     */
    public static function use(string $alias): Model
    {
        return static::$instances[$alias] ??= static::load($alias);
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
