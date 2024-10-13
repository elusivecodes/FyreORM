<?php
declare(strict_types=1);

namespace Fyre\ORM\Relationships;

use Closure;
use Fyre\Entity\Entity;
use Fyre\ORM\Exceptions\OrmException;
use Fyre\ORM\Model;
use Fyre\ORM\ModelRegistry;
use Fyre\ORM\Queries\SelectQuery;
use Fyre\Utility\Inflector;

use function array_key_exists;
use function array_merge;
use function count;
use function in_array;
use function is_numeric;

/**
 * Relationship
 */
abstract class Relationship
{
    protected string $bindingKey;

    protected string $className;

    protected array $conditions = [];

    protected bool $dependent = false;

    protected string $foreignKey;

    protected string $name;

    protected string $propertyName;

    protected Model $source;

    protected string $strategy = 'select';

    protected Model $target;

    protected array $validStrategies = ['select', 'subquery'];

    /**
     * New relationship constructor.
     *
     * @param string $name The relationship name.
     * @param array $options The relationship options.
     *
     * @throws OrmException if the strategy is not valid.
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
            'strategy',
            'conditions',
            'dependent',
        ];

        foreach ($defaults as $property) {
            if (!array_key_exists($property, $options)) {
                continue;
            }

            $this->$property = $options[$property];
        }

        if (!in_array($this->strategy, $this->validStrategies)) {
            throw OrmException::forInvalidStrategy($this->strategy);
        }

        $this->className ??= $this->name;
    }

    /**
     * Build join data.
     *
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
                'conditions' => array_merge([$joinCondition], $this->conditions, $options['conditions']),
            ],
        ];
    }

    /**
     * Find related data for entities.
     *
     * @param array $entities The entities.
     * @param array $data The find data.
     * @param SelectQuery $query The SelectQuery.
     */
    public function findRelated(array $entities, array $data, SelectQuery $query): void
    {
        $sourceValues = $this->getRelatedKeyValues($entities);
        $property = $this->getProperty();
        $hasMultiple = $this->hasMultiple();

        if ($sourceValues === []) {
            foreach ($entities as $entity) {
                if (!$hasMultiple) {
                    $entity->set($property, null);
                } else {
                    $entity->set($property, []);
                }
    
                $entity->setDirty($property, false);
            }
            return;
        }

        $data['strategy'] ??= $this->getStrategy();

        $target = $this->getTarget();

        if ($this->isOwningSide()) {
            $sourceKey = $this->getBindingKey();
            $targetKey = $this->getForeignKey();
        } else {
            $sourceKey = $this->getForeignKey();
            $targetKey = $this->getBindingKey();
        }

        if (array_key_exists('fields', $data) || (array_key_exists('autoFields', $data) && !$data['autoFields'])) {
            $data['fields'] ??= [];
            $data['fields'][] = $target->aliasField($targetKey, $this->name);
        }

        $data['alias'] = $this->name;

        $data = array_merge($query->getOptions(), $data);

        $newQuery = $target->find($data);

        if ($data['strategy'] === 'subquery') {
            $this->findRelatedSubquery($newQuery, $query);
        } else {
            $this->findRelatedConditions($newQuery, $sourceValues);
        }

        $allChildren = $newQuery->toArray();

        foreach ($entities as $entity) {
            $sourceValue = $entity->get($sourceKey);

            $children = [];
            foreach ($allChildren as $child) {
                $targetValue = $child->get($targetKey);

                if ($sourceValue !== $targetValue) {
                    continue;
                }

                $children[] = $child;

                if (!$hasMultiple) {
                    break;
                }
            }

            if (!$hasMultiple) {
                $entity->set($property, $children[0] ?? null);
            } else {
                $entity->set($property, $children);
            }

            $entity->setDirty($property, false);
        }
    }

    /**
     * Get the binding key.
     *
     * @return string The binding key.
     */
    public function getBindingKey(): string
    {
        return $this->bindingKey ??= $this->source->getPrimaryKey()[0] ?? '';
    }

    /**
     * Get the conditions.
     *
     * @return array $conditions The conditions.
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }

    /**
     * Get the foreign key.
     *
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
     *
     * @return string The relationship name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the relationship property name.
     *
     * @return string The relationship property name.
     */
    public function getProperty(): string
    {
        return $this->propertyName ??= static::propertyName($this->name, $this->hasMultiple());
    }

    /**
     * Get the source Model.
     *
     * @return Model The source Model.
     */
    public function getSource(): Model
    {
        return $this->source;
    }

    /**
     * Get the strategy.
     *
     * @return string The strategy.
     */
    public function getStrategy(): string
    {
        return $this->strategy;
    }

    /**
     * Get the target Model.
     *
     * @return Model The target Model.
     */
    public function getTarget(): Model
    {
        return $this->target ??= ModelRegistry::use($this->className);
    }

    /**
     * Determine if the relationship has multiple related items.
     *
     * @return bool TRUE if the relationship has multiple related items, otherwise FALSE.
     */
    public function hasMultiple(): bool
    {
        return true;
    }

    /**
     * Determine if the relationship is dependent.
     *
     * @return bool TRUE if the relationship is dependent, otherwise FALSE.
     */
    public function isDependent(): bool
    {
        return $this->dependent;
    }

    /**
     * Determine if the source is the owning side of the relationship.
     *
     * @return bool TRUE if the source is the owning side of the relationship, otherwise FALSE.
     */
    public function isOwningSide(): bool
    {
        return true;
    }

    /**
     * Save related data from an entity.
     *
     * @param Entity $entity The entity.
     * @return bool TRUE if the save was successful, otherwise FALSE.
     */
    abstract public function saveRelated(Entity $entity): bool;

    /**
     * Remove related data from entities.
     *
     * @param array $entities The entities.
     * @param array $options The options for deleting.
     * @return bool TRUE if the unlink was successful, otherwise FALSE.
     */
    public function unlinkAll(array $entities, array $options = []): bool
    {
        $sourceValues = $this->getRelatedKeyValues($entities);

        if ($sourceValues === []) {
            return true;
        }

        $conditions = $options['conditions'] ?? [];
        unset($options['conditions']);

        $target = $this->getTarget();

        $query = $target->find([
            'alias' => $this->name,
            'conditions' => $conditions,
        ]);

        $this->findRelatedConditions($query, $sourceValues);

        $relations = $query->toArray();

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

        foreach ($relations as $relation) {
            $relation->set($foreignKey, null);
        }

        if (!$target->saveMany($relations, $options)) {
            return false;
        }

        return true;
    }

    /**
     * Attach the find related conditions to a query.
     *
     * @param SelectQuery $newQuery The new SelectQuery.
     * @param array $sourceValues The source values.
     */
    protected function findRelatedConditions(SelectQuery $newQuery, array $sourceValues): void
    {
        if ($this->isOwningSide()) {
            $targetKey = $this->getForeignKey();
        } else {
            $targetKey = $this->getBindingKey();
        }

        $target = $this->getTarget();
        $targetKey = $target->aliasField($targetKey, $this->name);

        if (count($sourceValues) > 1) {
            $containConditions = [$targetKey.' IN' => $sourceValues];
        } else {
            $containConditions = [$targetKey => $sourceValues[0]];
        }

        $newQuery->where($containConditions);
    }

    /**
     * Attach the find related subquery to a query.
     *
     * @param SelectQuery $newQuery The new SelectQuery.
     * @param SelectQuery $query The SelectQuery.
     */
    protected function findRelatedSubquery(SelectQuery $newQuery, SelectQuery $query): void
    {
        if ($this->isOwningSide()) {
            $sourceKey = $this->getBindingKey();
            $targetKey = $this->getForeignKey();
        } else {
            $sourceKey = $this->getForeignKey();
            $targetKey = $this->getBindingKey();
        }

        $target = $this->getTarget();
        $targetKey = $target->aliasField($targetKey, $this->name);

        $alias = $query->getAlias();
        $sourceKey = $this->source->aliasField($sourceKey, $alias);

        $query = clone $query;

        $fields = $groupBy = [$sourceKey];
        $orderBy = $query->getOrderBy();
        $limit = $query->getLimit();
        $offset = $query->getOffset();

        if (!$limit && $orderBy === []) {
            $limit = null;
            $offset = 0;
        } else {
            $columns = $query->getSelect();
            foreach ($orderBy as $key => $value) {
                if (is_numeric($key) || !array_key_exists($key, $columns)) {
                    continue;
                }

                $fields[$key] = $columns[$key];
            }
        }

        $query
            ->select($fields, true)
            ->contain([], true)
            ->groupBy($groupBy, true)
            ->orderBy($orderBy, true)
            ->having([], true)
            ->limit($limit)
            ->offset($offset)
            ->epilog('');

        Closure::bind(function(): void {
            $this->autoAlias = false;
        }, $query, $query)->__invoke();

        $newQuery->join([
            [
                'table' => $query,
                'alias' => $alias,
                'type' => 'INNER',
                'conditions' => [
                    $sourceKey.' = '.$targetKey,
                ],
            ],
        ]);
    }

    /**
     * Get the related key values.
     *
     * @param array $entities The entities.
     * @return array The related key values.
     */
    protected function getRelatedKeyValues(array $entities): array
    {
        if ($this->isOwningSide()) {
            $sourceKey = $this->getBindingKey();
        } else {
            $sourceKey = $this->getForeignKey();
        }

        $sourceValues = [];

        foreach ($entities as $entity) {
            if ($entity->isEmpty($sourceKey)) {
                continue;
            }

            $sourceValues[] = $entity->get($sourceKey);
        }

        return $sourceValues;
    }

    /**
     * Get a foreign key from a model alias.
     *
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
     *
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
