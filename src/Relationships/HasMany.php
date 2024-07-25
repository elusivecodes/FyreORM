<?php
declare(strict_types=1);

namespace Fyre\ORM\Relationships;

use Fyre\DB\QueryGenerator;
use Fyre\Entity\Entity;

use function array_filter;
use function array_map;

/**
 * HasMany
 */
class HasMany extends Relationship
{
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
        $children = $entity->get($property);

        if ($children === null) {
            return true;
        }

        $children = array_filter(
            $children,
            fn(mixed $child): bool => $child && $child instanceof Entity
        );

        $bindingKey = $this->getBindingKey();
        $foreignKey = $this->getForeignKey();

        $bindingValue = $entity->get($bindingKey);

        foreach ($children as $child) {
            $child->set($foreignKey, null);
            $child->set($foreignKey, $bindingValue);
        }

        $preserveConditions = $this->excludeConditions($children);

        if (!$this->unlinkAll([$entity], $options + ['conditions' => $preserveConditions])) {
            return false;
        }

        if (!$this->getTarget()->saveMany($children, $options)) {
            return false;
        }

        return true;
    }

    /**
     * Build exclusion conditions for related entities.
     *
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

        foreach ($relations as $relation) {
            $preserveValues[] = $relation->extract($targetKeys);
        }

        $targetKeys = array_map(
            fn(string $foreignKey): string => $target->aliasField($foreignKey, $this->name),
            $targetKeys
        );

        return [
            'not' => QueryGenerator::normalizeConditions($targetKeys, $preserveValues),
        ];
    }
}
