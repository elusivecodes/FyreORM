<?php
declare(strict_types=1);

namespace Fyre\ORM\Relationships;

use
    Fyre\Entity\Entity,
    Fyre\ORM\Model,
    Fyre\ORM\ModelRegistry;

use function
    array_key_exists,
    array_merge,
    implode,
    natsort;

/**
 * ManyToMany
 */
class ManyToMany extends Relationship
{

    protected Model|null $joinModel = null;

    protected string $joinAlias;

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
    }

    /**
     * Build join data.
     * @param array $options The join options.
     * @return array The join data.
     */
    public function buildJoins(array $options = []): array
    {
        $targetAlias = $this->getTarget()->getAlias();

        $sourceJoins = $this->getSource()->getRelationship($this->joinAlias)->buildJoins([
            'sourceAlias' => $options['sourceAlias'] ?? null,
            'type' => $options['type'] ?? null
        ]);

        $targetJoins = $this->getJoinModel()->getRelationship($targetAlias)->buildJoins([
            'alias' => $options['alias'] ?? $this->name,
            'type' => $options['type'] ?? null,
            'conditions' => $options['conditions'] ?? null
        ]);

        return array_merge($sourceJoins, $targetJoins);
    }

    /**
     * Find related data for entities.
     * @param array $entities The entities.
     * @param array $data The find data.
     */
    public function findRelated(array $entities, array $data): void
    {
        $conditions = $this->containConditions($entities);

        if ($conditions === []) {
            return;
        }

        $joinModel = $this->getJoinModel();
        $target = $this->getTarget();
        $property = $this->getProperty();
        $bindingKey = $this->getBindingKey();
        $foreignKey = $this->getForeignKey();
        $targetAlias = $target->getAlias();
        $joinProperty = $joinModel->getRelationship($targetAlias)->getProperty();

        if (array_key_exists('fields', $data) || (array_key_exists('autoFields', $data) && !$data['autoFields'])) {
            $data['fields'] ??= [];
            $data['fields'][] = $joinModel->aliasField($foreignKey);
        }

        $data['conditions'] ??= [];
        $data['conditions'][] = $conditions;

        $contain = $data['contain'];
        $data['contain'] = [$targetAlias => $contain];

        $allChildren = array_map(
            function(Entity $child) use ($joinProperty): Entity {
                $realChild = $child->get($joinProperty);
                $child->unset($joinProperty);

                $realChild->set('_joinData', $child);
                $realChild->setDirty('_joinData', false);

                return $realChild;
            },
            $joinModel->find($data)->all()
        );

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
        if (!$this->joinModel) {
            $sourceAlias = $this->getSource()->getAlias();
            $target = $this->getTarget();
            $targetAlias = $target->getAlias();
    
            $this->joinModel = ModelRegistry::use($this->joinAlias);

            if (!$this->joinModel->hasRelationship($sourceAlias)) {
                $this->joinModel->belongsTo($sourceAlias, [
                    'bindingKey' => $this->getBindingKey(),
                    'foreignKey' => $this->getForeignKey()
                ]);
            }
    
            if (!$this->joinModel->hasRelationship($targetAlias)) {
                $targetRelationship = $target->getRelationship($this->joinAlias);
                $this->joinModel->belongsTo($targetAlias, [
                    'bindingKey' => $targetRelationship->getBindingKey(),
                    'foreignKey' => $targetRelationship->getForeignKey()
                ]);
            }
        }

        return $this->joinModel;
    }

    /**
     * Get the source Model.
     * @return Model The source Model.
     */
    public function getSource(): Model
    {
        $source = parent::getSource();

        if (!$source->hasRelationship($this->joinAlias)) {
            $source->hasMany($this->joinAlias, [
                'foreignKey' => $this->getForeignKey(),
                'bindingKey' => $this->getBindingKey(),
                'dependent' => true
            ]);
        }

        return $source;
    }

    /**
     * Get the target Model.
     * @return Model The target Model.
     */
    public function getTarget(): Model
    {
        $target = parent::getTarget();

        if (!$target->hasRelationship($this->joinAlias)) {
            $target->hasMany($this->joinAlias, [
                'dependent' => true
            ]);
        }

        return $target;
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

        $saveEntities = [];
        $relations = [];
        foreach ($entities AS $entity) {
            $children = $entity->get($property);

            if ($children === null) {
                continue;
            }

            $saveEntities[] = $entity;

            foreach ($children AS $child) {
                if (!$child || !$child instanceof Entity) {
                    continue;
                }

                $relations[] = $child;
            }
        }

        if ($saveEntities === []) {
            return true;
        }

        if (!$this->getSource()->getRelationship($this->joinAlias)->unlinkAll($saveEntities, $options)) {
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
        $targetRelationship = $target->getRelationship($this->joinAlias);
        $targetBindingKey = $targetRelationship->getBindingKey();
        $targetForeignKey = $targetRelationship->getForeignKey();

        $joinEntities = [];
        foreach ($saveEntities AS $entity) {
            $children = $entity->get($property);
            $bindingValue = $entity->get($bindingKey);

            foreach ($children AS $child) {
                if (!$child || !$child instanceof Entity) {
                    continue;
                }

                $targetBindingValue = $child->get($targetBindingKey);

                $child = $joinModel->newEmptyEntity();
                $child->set($foreignKey, $bindingValue);
                $child->set($targetForeignKey, $targetBindingValue);

                $joinEntities[] = $child;
            }
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
        return true;
    }

}
