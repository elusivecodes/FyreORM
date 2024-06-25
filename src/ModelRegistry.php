<?php
declare(strict_types=1);

namespace Fyre\ORM;

use function array_key_exists;
use function array_splice;
use function class_exists;
use function in_array;
use function is_subclass_of;
use function trim;

/**
 * ModelRegistry
 */
abstract class ModelRegistry
{
    protected static string $defaultModelClass = Model::class;

    protected static array $instances = [];

    protected static array $namespaces = [];

    /**
     * Add a namespace for loading models.
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
     * Clear all namespaces and models.
     */
    public static function clear(): void
    {
        static::$namespaces = [];
        static::$instances = [];
    }

    /**
     * Create a default Model.
     *
     * @return Model The Model.
     */
    public static function createDefaultModel(): Model
    {
        $modelClass = static::$defaultModelClass;

        return new $modelClass();
    }

    /**
     * Get the default model class name.
     *
     * @return string The default model class name.
     */
    public static function getDefaultModelClass(): string
    {
        return static::$defaultModelClass;
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
     * Determine if a model is loaded.
     *
     * @param string $alias The model alias.
     * @return bool TRUE if the model is loaded, otherwise FALSE.
     */
    public static function isLoaded(string $alias): bool
    {
        return array_key_exists($alias, static::$instances);
    }

    /**
     * Load a Model.
     *
     * @param string $alias The model alias.
     * @return Model The Model.
     */
    public static function load(string $alias): Model
    {
        foreach (static::$namespaces as $namespace) {
            $fullClass = $namespace.$alias.'Model';

            if (class_exists($fullClass) && is_subclass_of($fullClass, Model::class)) {
                return new $fullClass();
            }
        }

        return static::createDefaultModel()->setAlias($alias);
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
     * Set the default model class name.
     *
     * @param string $defaultModelClass The default model class name.
     */
    public static function setDefaultModelClass(string $defaultModelClass): void
    {
        static::$defaultModelClass = $defaultModelClass;
    }

    /**
     * Unload a model.
     *
     * @param string $alias The model alias.
     * @return bool TRUE if the model was removed, otherwise FALSE.
     */
    public static function unload(string $alias): bool
    {
        if (!array_key_exists($alias, static::$instances)) {
            return false;
        }

        unset(static::$instances[$alias]);

        return true;
    }

    /**
     * Load a shared Model instance.
     *
     * @param string $alias The model alias.
     * @return Model The Model.
     */
    public static function use(string $alias): Model
    {
        return static::$instances[$alias] ??= static::load($alias);
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
