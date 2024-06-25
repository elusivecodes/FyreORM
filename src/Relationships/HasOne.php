<?php
declare(strict_types=1);

namespace Fyre\ORM\Relationships;

use Fyre\Entity\Entity;

/**
 * HasOne
 */
class HasOne extends Relationship
{
    protected string $strategy = 'join';

    protected array $validStrategies = ['join', 'select'];

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
     * Save related data from an entity.
     *
     * @param Entity $entity The entity.
     * @param array $options The options for saving.
     * @return bool TRUE if the save was successful, otherwise FALSE.
     */
    public function saveRelated(Entity $entity, array $options = []): bool
    {
        $property = $this->getProperty();
        $child = $entity->get($property);

        if (!$child || !$child instanceof Entity) {
            return true;
        }

        $bindingKey = $this->getBindingKey();
        $foreignKey = $this->getForeignKey();

        $bindingValue = $entity->get($bindingKey);
        $child->set($foreignKey, $bindingValue);

        if (!$this->getTarget()->save($child, $options)) {
            return false;
        }

        return true;
    }
}
