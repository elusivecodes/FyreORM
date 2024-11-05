<?php
declare(strict_types=1);

namespace Fyre\ORM\Relationships;

use Fyre\Entity\Entity;

/**
 * BelongsTo
 */
class BelongsTo extends Relationship
{
    protected string $strategy = 'join';

    protected array $validStrategies = ['join', 'select'];

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
     * Determine if the relationship has multiple related items.
     *
     * @return bool TRUE if the relationship has multiple related items, otherwise FALSE.
     */
    public function hasMultiple(): bool
    {
        return false;
    }

    /**
     * Determine if the source is the owning side of the relationship.
     *
     * @return bool TRUE if the source is the owning side of the relationship, otherwise FALSE.
     */
    public function isOwningSide(): bool
    {
        return false;
    }

    /**
     * Save related data from an entity.
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

        $parent->saveState();

        $options['saveState'] = false;

        if (!$this->getTarget()->save($parent, $options)) {
            return false;
        }

        $foreignKey = $this->getForeignKey();
        $bindingKey = $this->getBindingKey();

        $bindingValue = $parent->get($bindingKey);
        $entity->set($foreignKey, null);
        $entity->set($foreignKey, $bindingValue);

        return true;
    }

    /**
     * Remove related data from entities.
     *
     * @param array $entities The entities.
     * @param array $options The options for deleting.
     * @return bool TRUE if the unlink was successful, otherwise FALSE.
     */
    public function unlinkAll(array $entities, array $options = []): bool
    {
        return true;
    }
}
