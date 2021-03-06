<?php
declare(strict_types=1);

namespace Fyre\ORM\Relationships;

use
    Fyre\Entity\Entity;

/**
 * HasOne
 */
class HasOne extends Relationship
{

    protected string $strategy = 'join';

    protected array $validStrategies = ['join', 'select'];

    /**
     * Determine if the relationship has multiple related items.
     * @return bool TRUE if the relationship has multiple related items, otherwise FALSE.
     */
    public function hasMultiple(): bool
    {
        return false;
    }

    /**
     * Save related data from entities.
     * @param array $entities The entities.
     * @param array $options The options for saving.
     * @return bool TRUE if the save was successful, otherwise FALSE.
     */
    public function saveRelated(array $entities, array $options = []): bool
    {
        $property = $this->getProperty();
        $bindingKey = $this->getBindingKey();
        $foreignKey = $this->getForeignKey();

        $relations = [];
        foreach ($entities AS $entity) {
            $child = $entity->get($property);

            if (!$child || !$child instanceof Entity) {
                continue;
            }

            $bindingValue = $entity->get($bindingKey);
            $child->set($foreignKey, $bindingValue);

            $relations[] = $child;
        }

        if ($relations === []) {
            return true;
        }

        if (!$this->getTarget()->saveMany($relations, $options)) {
            return false;
        }

        return true;
    }

}
