<?php
declare(strict_types=1);

namespace Fyre\ORM\Relationships;

use Fyre\Entity\Entity;
use Fyre\ORM\Relationship;
use Fyre\Utility\Traits\MacroTrait;
use Traversable;

/**
 * BelongsTo
 */
class BelongsTo extends Relationship
{
    use MacroTrait;

    protected string $strategy = 'join';

    protected array $validStrategies = ['join', 'select'];

    /**
     * Call a method on the target model.
     *
     * @param string $method The method name.
     * @param array $arguments The method arguments.
     * @return mixed The result.
     */
    public function __call(string $method, array $arguments): mixed
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $arguments);
        }

        return parent::__call($method, $arguments);
    }

    /**
     * Get the binding key.
     *
     * @return string The binding key.
     */
    public function getBindingKey(): string
    {
        return $this->bindingKey ??= $this->getTarget()->getPrimaryKey()[0];
    }

    /**
     * Get the foreign key.
     *
     * @return string The foreign key.
     */
    public function getForeignKey(): string
    {
        return $this->foreignKey ??= $this->modelKey($this->name);
    }

    /**
     * Determine whether the relationship has multiple related items.
     *
     * @return bool TRUE if the relationship has multiple related items, otherwise FALSE.
     */
    public function hasMultiple(): bool
    {
        return false;
    }

    /**
     * Determine whether the source is the owning side of the relationship.
     *
     * @return bool TRUE if the source is the owning side of the relationship, otherwise FALSE.
     */
    public function isOwningSide(): bool
    {
        return false;
    }

    /**
     * Save related data for an entity.
     *
     * @param Entity $entity The entity.
     * @param array $options The options for saving.
     * @return bool TRUE if the save was successful, otherwise FALSE.
     */
    public function saveRelated(Entity $entity, array $options = []): bool
    {
        $property = $this->getProperty();
        $parent = $entity->get($property);

        if (!$parent || !$parent instanceof Entity) {
            return true;
        }

        if (!$this->getTarget()->save($parent, $options)) {
            return false;
        }

        $foreignKey = $this->getForeignKey();
        $bindingKey = $this->getBindingKey();

        $bindingValue = $parent->get($bindingKey);

        if ($entity->get($foreignKey) !== $bindingValue) {
            $entity->set($foreignKey, $bindingValue, ['temporary' => true]);
        }

        return true;
    }

    /**
     * Remove related data from entities.
     *
     * @param array|Traversable $entities The entities.
     * @param array $options The options for deleting.
     * @return bool TRUE if the unlink was successful, otherwise FALSE.
     */
    public function unlinkAll(array|Traversable $entities, array $options = []): bool
    {
        return true;
    }
}
