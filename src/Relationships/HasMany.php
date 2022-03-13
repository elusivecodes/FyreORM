<?php
declare(strict_types=1);

namespace Fyre\ORM\Relationships;

use
    Fyre\DB\QueryGenerator,
    Fyre\Entity\Entity,
    Fyre\ORM\Model;

use function
    array_map;

/**
 * HasMany
 */
class HasMany extends Relationship
{

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

        $unlinkEntities = [];
        $preserveEntities = [];
        $relations = [];
        foreach ($entities AS $entity) {
            $children = $entity->get($property);

            if ($children === null) {
                continue;
            }

            $unlinkEntities[] = $entity;

            $bindingValue = $entity->get($bindingKey);

            foreach ($children AS $child) {
                if (!$child || !$child instanceof Entity) {
                    continue;
                }

                $child->set($foreignKey, $bindingValue);

                $preserveEntities[] = $child;
                $relations[] = $child;
            }
        }

        $preserveConditions = $this->excludeConditions($preserveEntities);

        if ($unlinkEntities !== [] && !$this->unlinkAll($unlinkEntities, $options + ['conditions' => $preserveConditions])) {
            return false;
        }

        if ($relations !== [] && !$this->getTarget()->saveMany($relations, $options)) {
            return false;
        }

        return true;
    }

    /**
     * Build exclusion conditions for related entities.
     * @param array $relations The related entities.
     * @return array The exclusion conditions.
     */
    protected function excludeConditions(array $relations): array
    {
        if ($relations === []) {
            return [];
        }

        $target = $this->getTarget();
        $targetKeys = $target->getPrimaryKey();
        $preserveValues = [];

        foreach ($relations AS $relation) {
            $preserveValues[] = $relation->extract($targetKeys);
        }

        $targetKeys = array_map(
            fn($foreignKey) => $target->aliasField($foreignKey),
            $targetKeys
        );

        return [
            'not' => QueryGenerator::normalizeConditions($targetKeys, $preserveValues)
        ];
    }

}
