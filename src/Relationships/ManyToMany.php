<?php
declare(strict_types=1);

namespace Fyre\ORM\Relationships;

use Fyre\Entity\Entity;
use Fyre\ORM\Model;
use Fyre\ORM\ModelRegistry;
use Fyre\ORM\Queries\SelectQuery;

use function array_key_exists;
use function array_merge;
use function implode;
use function is_array;
use function natsort;

/**
 * ManyToMany
 */
class ManyToMany extends Relationship
{

    protected Model|null $joinModel = null;

    protected HasMany $sourceRelationship;

    protected BelongsTo $targetRelationship;

    protected string $joinAlias;

    protected string $targetForeignKey;

    /**
     * New relationship constructor.
     * @param string $name The relationship name.
     * @param array $options The relationship options.
     */
    public function __construct(string $name, array $options = [])
    {
        parent::__construct($name, $options);

        if (array_key_exists('through', $options)) {
            $this->joinAlias = $options['through'];
        } else {
            $aliases = [
                $this->source->getAlias(),
                $this->name
            ];

            natsort($aliases);

            $this->joinAlias = implode('', $aliases);
        }

        if (array_key_exists('targetForeignKey', $options)) {
            $this->targetForeignKey = $options['targetForeignKey'];
        }
    }

    /**
     * Build join data.
     * @param array $options The join options.
     * @return array The join data.
     */
    public function buildJoins(array $options = []): array
    {
        $sourceJoins = $this->getSourceRelationship()->buildJoins([
            'sourceAlias' => $options['sourceAlias'] ?? null,
            'type' => $options['type'] ?? null
        ]);

        $targetJoins = $this->getTargetRelationship()->buildJoins([
            'alias' => $options['alias'] ?? null,
            'type' => $options['type'] ?? null,
            'conditions' => $options['conditions'] ?? null
        ]);

        return array_merge($sourceJoins, $targetJoins);
    }

    /**
     * Find related data for entities.
     * @param array $entities The entities.
     * @param array $data The find data.
     * @param SelectQuery|null $query The SelectQuery.
     */
    public function findRelated(array $entities, array $data, SelectQuery|null $query = null): void
    {
        $data['strategy'] ??= $this->getStrategy();

        if ($query && $data['strategy'] === 'subquery') {
            $conditions = $this->containConditionSubquery($query);
        } else {
            $conditions = $this->containConditions($entities);
        }

        if ($conditions === []) {
            return;
        }

        $joinModel = $this->getJoinModel();
        $property = $this->getProperty();
        $bindingKey = $this->getBindingKey();
        $foreignKey = $this->getForeignKey();
        $targetRelationship = $this->getTargetRelationship();
        $joinProperty = $targetRelationship->getProperty();
        $targetName = $targetRelationship->getName();

        if (array_key_exists('fields', $data) || (array_key_exists('autoFields', $data) && !$data['autoFields'])) {
            $data['fields'] ??= [];
            $data['fields'][] = $joinModel->aliasField($foreignKey);
        }

        $data['conditions'] ??= [];
        $data['conditions'][] = $conditions;

        $contain = $data['contain'];
        $data['contain'] = [$targetName => $contain];

        $data = array_merge($query->getOptions(), $data);

        $hasRelationship = $joinModel->hasRelationship($targetName);

        if (!$hasRelationship) {
            $joinModel->addRelationship($targetRelationship);
        }

        $children = $joinModel
            ->find($data)
            ->all();

        $allChildren = array_map(
            function(Entity $child) use ($joinProperty): Entity {
                $realChild = $child->get($joinProperty);
                $child->unset($joinProperty);

                $realChild->set('_joinData', $child);
                $realChild->setDirty('_joinData', false);

                return $realChild;
            },
            $children
        );

        if (!$hasRelationship) {
            $joinModel->removeRelationship($targetName);
        }

        foreach ($entities AS $entity) {
            $bindingValue = $entity->get($bindingKey);

            $children = [];
            foreach ($allChildren AS $child) {
                $foreignValue = $child->_joinData->get($foreignKey);

                if ($bindingValue !== $foreignValue) {
                    continue;
                }

                $children[] = clone $child;
            }

            $entity->set($property, $children);
            $entity->setDirty($property, false);
        }
    }

    /**
     * Get the join Model.
     * @return Model The join Model.
     */
    public function getJoinModel(): Model
    {
        return $this->joinModel ??= ModelRegistry::use($this->joinAlias);
    }

    /**
     * Get the source relationship.
     * @return HasMany The source relationship.
     */
    public function getSourceRelationship(): HasMany
    {
        return $this->sourceRelationship ??= new HasMany($this->joinAlias, [
            'source' => $this->getSource(),
            'foreignKey' => $this->getForeignKey(),
            'bindingKey' => $this->getBindingKey(),
            'dependent' => true
        ]);
    }

    /**
     * Get the target foreign key.
     * @return string The target foreign key.
     */
    public function getTargetForeignKey(): string
    {
        return $this->targetForeignKey ??= static::modelKey(
            $this->getTarget()->getAlias()
        );
    }

    /**
     * Get the target relationship.
     * @return BelongsTo The target relationship.
     */
    public function getTargetRelationship(): BelongsTo
    {
        return $this->targetRelationship ??= new BelongsTo($this->getTarget()->getAlias(), [
            'source' => $this->getJoinModel(),
            'foreignKey' => $this->getTargetForeignKey()
        ]);
    }

    /**
     * Save related data from an entity.
     * @param Entity $entity The entity.
     * @param array $options The options for saving.
     * @return bool TRUE if the save was successful, otherwise FALSE.
     */
    public function saveRelated(Entity $entity, array $options = []): bool
    {
        $property = $this->getProperty();
        $relations = $entity->get($property);

        if ($relations === null) {
            return true;
        }

        $relations = array_filter(
            $relations,
            fn(mixed $relation): bool => $relation && $relation instanceof Entity
        );

        if (!$this->getSourceRelationship()->unlinkAll([$entity], $options)) {
            return false;
        }

        if ($relations === []) {
            return true;
        }

        $target = $this->getTarget();

        if (!$target->saveMany($relations, $options)) {
            return false;
        }

        $joinModel = $this->getJoinModel();
        $bindingKey = $this->getBindingKey();
        $foreignKey = $this->getForeignKey();
        $targetRelationship = $this->getTargetRelationship();
        $targetBindingKey = $targetRelationship->getBindingKey();
        $targetForeignKey = $targetRelationship->getForeignKey();
        $bindingValue = $entity->get($bindingKey);

        $joinEntities = [];
        foreach ($relations AS $relation) {
            $joinData = $relation->get('_joinData') ?? [];

            if ($joinData instanceof Entity) {
                $joinEntity = $joinData;
            } else if (is_array($joinData)) {
                $joinEntity = $joinModel->newEntity($joinData);
            } else {
                $joinEntity = $joinModel->newEmptyEntity();
            }

            $targetBindingValue = $relation->get($targetBindingKey);

            $joinEntity->set($foreignKey, $bindingValue);
            $joinEntity->set($targetForeignKey, $targetBindingValue);

            $joinEntities[] = $joinEntity;
        }

        if (!$joinModel->saveMany($joinEntities, $options)) {
            return false;
        }

        return true;
    }

    /**
     * Remove related data from entities.
     * @param array $entities The entities.
     * @param array $options The options for deleting.
     * @return bool TRUE if the unlink was successful, otherwise FALSE.
     */
    public function unlinkAll(array $entities, array $options = []): bool
    {
        return $this->getSourceRelationship()->unlinkAll($entities, $options);
    }

}
