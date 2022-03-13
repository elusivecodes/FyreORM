<?php
declare(strict_types=1);

namespace Fyre\ORM\Relationships;

use
    Fyre\ORM\Model,
    Fyre\ORM\ModelRegistry,
    Fyre\Utility\Inflector;

use function
    array_key_exists,
    array_merge,
    count;

/**
 * Relationship
 */
abstract class Relationship
{

    protected Model $source;

    protected Model $target;

    protected string $name;

    protected string $className;

    protected string $propertyName;

    protected string $foreignKey;

    protected string $bindingKey;

    protected array $conditions = [];

    protected bool $dependent = false;

    protected bool $isOwningSide = true;

    /**
     * New relationship constructor.
     * @param string $name The relationship name.
     * @param array $options The relationship options.
     */
    public function __construct(string $name, array $options = [])
    {
        $this->name = $name;

        $defaults = [
            'source',
            'className',
            'propertyName',
            'foreignKey',
            'bindingKey',
            'conditions',
            'dependent'
        ];

        foreach ($defaults AS $property) {
            if (!array_key_exists($property, $options)) {
                continue;
            }

            $this->$property = $options[$property];
        }

        $this->className ??= $this->name;
    }

    /**
     * Build join data.
     * @param array $options The join options.
     * @return array The join data.
     */
    public function buildJoins(array $options = []): array
    {
        $source = $this->getSource();
        $target = $this->getTarget();

        $options['alias'] ??= $this->name;
        $options['sourceAlias'] ??= $source->getAlias();
        $options['type'] ??= 'LEFT';
        $options['conditions'] ??= [];

        if ($this->isOwningSide()) {
            $sourceKey = $this->getBindingKey();
            $targetKey = $this->getForeignKey();
        } else {
            $sourceKey = $this->getForeignKey();
            $targetKey = $this->getBindingKey();
        }

        $joinCondition = $target->aliasField($targetKey, $options['alias']).' = '.$source->aliasField($sourceKey, $options['sourceAlias']);

        return [
            $options['alias'] => [
                'table' => $target->getTable(),
                'type' => $options['type'],
                'conditions' => array_merge([$joinCondition], $this->conditions, $options['conditions'])
            ]
        ];
    }

    /**
     * Determine if the relationship can be joined.
     * @return bool TRUE if the relationship can be joined, otherwise FALSE.
     */
    public function canBeJoined(): bool
    {
        return false;
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

        $target = $this->getTarget();
        $property = $this->getProperty();
        $canBeJoined = $this->canBeJoined();
        $bindingKey = $this->getBindingKey();
        $foreignKey = $this->getForeignKey();

        if (array_key_exists('fields', $data) || (array_key_exists('autoFields', $data) && !$data['autoFields'])) {
            $data['fields'] ??= [];
            $data['fields'][] = $target->aliasField($foreignKey);
        }

        $data['conditions'] = array_merge($conditions, $data['conditions'] ?? []);

        $allChildren = $target->find($data)->all();

        foreach ($entities AS $entity) {
            $bindingValue = $entity->get($bindingKey);

            $children = [];
            foreach ($allChildren AS $child) {
                $foreignValue = $child->get($foreignKey);

                if ($bindingValue !== $foreignValue) {
                    continue;
                }

                $children[] = $child;

                if ($canBeJoined) {
                    break;
                }
            }

            if ($canBeJoined) {
                $entity->set($property, $children[0] ?? null);
            } else {
                $entity->set($property, $children);
            }

            $entity->setDirty($property, false);
        }
    }

    /**
     * Get the binding key.
     * @return string The binding key.
     */
    public function getBindingKey(): string
    {
        return $this->bindingKey ??= $this->source->getPrimaryKey()[0] ?? '';
    }

    /**
     * Get the conditions.
     * @return array $conditions The conditions.
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }

    /**
     * Get the foreign key.
     * @return string The foreign key.
     */
    public function getForeignKey(): string
    {
        return $this->foreignKey ??= static::modelKey(
            $this->source->getAlias()
        );
    }

    /**
     * Get the relationship name.
     * @return string The relationship name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the relationship property name.
     * @return string The relationship property name.
     */
    public function getProperty(): string
    {
        return $this->propertyName ??= static::propertyName($this->name, !$this->canBeJoined());
    }

    /**
     * Get the source Model.
     * @return Model The source Model.
     */
    public function getSource(): Model
    {
        return $this->source;
    }

    /**
     * Get the target Model.
     * @return Model The target Model.
     */
    public function getTarget(): Model
    {
        return $this->target ??= ModelRegistry::use($this->className);
    }

    /**
     * Determine if the relationship is dependent.
     * @return bool TRUE if the relationship is dependent, otherwise FALSE.
     */
    public function isDependent(): bool
    {
        return $this->dependent;
    }

    /**
     * Determine if the source is the owning side of the relationship.
     * @return bool TRUE if the source is the owning side of the relationship, otherwise FALSE.
     */
    public function isOwningSide(): bool
    {
        return true;
    }

    /**
     * Save related data from entities.
     * @param array $entities The entities.
     * @return bool TRUE if the save was successful, otherwise FALSE.
     */
    abstract public function saveRelated(array $entities): bool;

    /**
     * Remove related data from entities.
     * @param array $entities The entities.
     * @param array $options The options for deleting.
     * @return bool TRUE if the unlink was successful, otherwise FALSE.
     */
    public function unlinkAll(array $entities, array $options = []): bool
    {
        $containConditions = $this->containConditions($entities);

        if ($containConditions === []) {
            return true;
        }

        $conditions = $options['conditions'] ?? [];
        unset($options['conditions']);

        $conditions = array_merge($containConditions, $conditions);

        $target = $this->getTarget();

        $relations = $target->find([
            'conditions' => $conditions
        ])->all();

        if ($relations === []) {
            return true;
        }

        $foreignKey = $this->getForeignKey();

        if ($this->isDependent() || !$target->getSchema()->isNullable($foreignKey)) {
            if (!$target->deleteMany($relations, $options)) {
                return false;
            }

            return true;
        }

        foreach ($relations AS $relation) {
            $relation->set($foreignKey, null);
        }

        if (!$target->saveMany($relations, $options)) {
            return false;
        }

        return true;
    }

    /**
     * Get the contain conditions for entities.
     * @param array $entities The entities.
     * @return array The contain conditions.
     */
    protected function containConditions(array $entities): array
    {
        if ($this->isOwningSide()) {
            $sourceKey = $this->getBindingKey();
            $targetKey = $this->getForeignKey();
        } else {
            $sourceKey = $this->getForeignKey();
            $targetKey = $this->getBindingKey();
        }

        $sourceValues = [];

        foreach ($entities AS $entity) {
            if ($entity->isEmpty($sourceKey)) {
                continue;
            }

            $sourceValues[] = $entity->get($sourceKey);
        }

        if ($sourceValues === []) {
            return [];
        }

        $target = $this->getTarget();
        $targetKey = $target->aliasField($targetKey);

        if (count($sourceValues) > 1) {
            $containConditions = [$targetKey.' IN' => $sourceValues];
        } else {
            $containConditions = [$targetKey => $sourceValues[0]];
        }

        return array_merge($containConditions, $this->conditions);
    }

    /**
     * Get a foreign key from a model alias.
     * @param string $alias The model alias.
     * @return string The foreign key.
     */
    protected static function modelKey(string $alias): string
    {
        $alias = Inflector::singularize($alias);
        $alias .= 'Id';

        return Model::tableize($alias);
    }

    /**
     * Get a property name from a model alias.
     * @param string $alias The model alias.
     * @param bool $plural Whether to use a plural name.
     * @return string The property name.
     */
    protected static function propertyName(string $alias, bool $plural = false): string
    {
        if (!$plural) {
            $alias = Inflector::singularize($alias);
        }

        return Model::tableize($alias);
    }

}
