<?php
declare(strict_types=1);

namespace Fyre\ORM;

use Fyre\Container\Container;
use Fyre\ORM\Exceptions\OrmException;

use function array_key_exists;
use function array_splice;
use function class_exists;
use function in_array;
use function is_subclass_of;
use function trim;

/**
 * ModelRegistry
 */
class ModelRegistry
{
    protected Container $container;

    protected string $defaultModelClass = Model::class;

    protected array $instances = [];

    protected array $namespaces = [];

    public function __construct(Container $container, array $namespaces = [])
    {
        $this->container = $container;

        foreach ($namespaces as $namespace) {
            $this->addNamespace($namespace);
        }
    }

    /**
     * Add a namespace for loading models.
     *
     * @param string $namespace The namespace.
     * @return static The ModelRegistry.
     */
    public function addNamespace(string $namespace): static
    {
        $namespace = static::normalizeNamespace($namespace);

        if (!in_array($namespace, $this->namespaces)) {
            $this->namespaces[] = $namespace;
        }

        return $this;
    }

    /**
     * Build a Model.
     *
     * @param string $alias The model class alias.
     * @return Model The Model.
     */
    public function build(string $classAlias): Model
    {
        foreach ($this->namespaces as $namespace) {
            $fullClass = $namespace.$classAlias.'Model';

            if (class_exists($fullClass) && is_subclass_of($fullClass, Model::class)) {
                return $this->container->build($fullClass);
            }
        }

        return static::createDefaultModel()->setClassAlias($classAlias);
    }

    /**
     * Clear all namespaces and models.
     */
    public function clear(): void
    {
        $this->namespaces = [];
        $this->instances = [];
    }

    /**
     * Create a default Model.
     *
     * @return Model The Model.
     */
    public function createDefaultModel(): Model
    {
        return $this->container->build($this->defaultModelClass);
    }

    /**
     * Get the default model class name.
     *
     * @return string The default model class name.
     */
    public function getDefaultModelClass(): string
    {
        return $this->defaultModelClass;
    }

    /**
     * Get the namespaces.
     *
     * @return array The namespaces.
     */
    public function getNamespaces(): array
    {
        return $this->namespaces;
    }

    /**
     * Determine whether a namespace exists.
     *
     * @param string $namespace The namespace.
     * @return bool TRUE if the namespace exists, otherwise FALSE.
     */
    public function hasNamespace(string $namespace): bool
    {
        $namespace = static::normalizeNamespace($namespace);

        return in_array($namespace, $this->namespaces);
    }

    /**
     * Determine whether a model is loaded.
     *
     * @param string $alias The model alias.
     * @return bool TRUE if the model is loaded, otherwise FALSE.
     */
    public function isLoaded(string $alias): bool
    {
        return array_key_exists($alias, $this->instances);
    }

    /**
     * Remove a namespace.
     *
     * @param string $namespace The namespace.
     * @return static The ModelRegistry.
     */
    public function removeNamespace(string $namespace): static
    {
        $namespace = static::normalizeNamespace($namespace);

        foreach ($this->namespaces as $i => $otherNamespace) {
            if ($otherNamespace !== $namespace) {
                continue;
            }

            array_splice($this->namespaces, $i, 1);
            break;
        }

        return $this;
    }

    /**
     * Set the default model class name.
     *
     * @param string $defaultModelClass The default model class name.
     * @return static The ModelRegistry.
     */
    public function setDefaultModelClass(string $defaultModelClass): static
    {
        $this->defaultModelClass = $defaultModelClass;

        return $this;
    }

    /**
     * Unload a model.
     *
     * @param string $alias The model alias.
     * @return static The ModelRegistry.
     */
    public function unload(string $alias): static
    {
        unset($this->instances[$alias]);

        return $this;
    }

    /**
     * Load a shared Model instance.
     *
     * @param string $alias The model alias.
     * @param string|null $classAlias The model class alias.
     * @return Model The Model.
     *
     * @throws OrmException if the alias is used by a different class.
     */
    public function use(string $alias, string|null $classAlias = null): Model
    {
        if (!array_key_exists($alias, $this->instances)) {
            $this->instances[$alias] = $classAlias && $classAlias !== $alias ?
                static::build($classAlias)->setAlias($alias) :
                static::build($alias);
        } else if ($classAlias && $this->instances[$alias]->getClassAlias() !== $classAlias) {
            throw OrmException::forAliasNotUnique($alias);
        }

        return $this->instances[$alias];
    }

    /**
     * Normalize a namespace
     *
     * @param string $namespace The namespace.
     * @return string The normalized namespace.
     */
    protected static function normalizeNamespace(string $namespace): string
    {
        return trim($namespace, '\\').'\\';
    }
}
