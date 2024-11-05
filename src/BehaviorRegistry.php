<?php
declare(strict_types=1);

namespace Fyre\ORM;

use Fyre\Container\Container;
use Fyre\ORM\Exceptions\OrmException;

use function class_exists;
use function in_array;
use function is_subclass_of;
use function trim;

/**
 * BehaviorRegistry
 */
class BehaviorRegistry
{
    protected array $behaviors = [];

    protected Container $container;

    protected array $namespaces = [];

    /**
     * New BehaviorRegistry constructor.
     *
     * @param Container $container The Container.
     */
    public function __construct(Container $container, array $namespaces = [])
    {
        $this->container = $container;

        foreach ($namespaces as $namespace) {
            $this->addNamespace($namespace);
        }
    }

    /**
     * Add a namespace for loading behaviors.
     *
     * @param string $namespace The namespace.
     */
    public function addNamespace(string $namespace): void
    {
        $namespace = static::normalizeNamespace($namespace);

        if (!in_array($namespace, $this->namespaces)) {
            $this->namespaces[] = $namespace;
        }
    }

    /**
     * Build a behavior.
     *
     * @param string $name The behavior name.
     * @param Model $model The Model.
     * @param array $options The behavior options.
     * @return Behavior The Behavior.
     *
     * @throws OrmException if the behavior does not exist.
     */
    public function build(string $name, Model $model, array $options = []): Behavior
    {
        $className = $this->find($name);

        if (!$className) {
            throw OrmException::forInvalidBehavior($name);
        }

        return $this->container->build($className, ['model' => $model, 'options' => $options]);
    }

    /**
     * Clear all namespaces and behaviors.
     */
    public function clear(): void
    {
        $this->namespaces = [];
        $this->behaviors = [];
    }

    /**
     * Find a behavior class.
     *
     * @param string $name The behavior name.
     * @return string|null The behavior class.
     */
    public function find(string $name): string|null
    {
        return $this->behaviors[$name] ??= $this->locate($name);
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
     * Determine if a namespace exists.
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
     * Remove a namespace.
     *
     * @param string $namespace The namespace.
     * @return bool TRUE If the namespace was removed, otherwise FALSE.
     */
    public function removeNamespace(string $namespace): bool
    {
        $namespace = static::normalizeNamespace($namespace);

        foreach ($this->namespaces as $i => $otherNamespace) {
            if ($otherNamespace !== $namespace) {
                continue;
            }

            array_splice($this->namespaces, $i, 1);

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
    protected function locate(string $name): string|null
    {
        $namespaces = $this->namespaces;

        if (!in_array('Fyre\ORM\Behaviors\\', $namespaces)) {
            $namespaces[] = 'Fyre\ORM\Behaviors\\';
        }

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
        return trim($namespace, '\\').'\\';
    }
}
