<?php
declare(strict_types=1);

namespace Fyre\ORM;

use Closure;
use Fyre\Collection\Collection;
use Fyre\Entity\Entity;
use Fyre\ORM\Exceptions\OrmException;
use Fyre\ORM\Queries\SelectQuery;
use Fyre\Utility\Inflector;
use Traversable;

use function array_key_exists;
use function array_merge;
use function count;
use function in_array;
use function is_numeric;
use function property_exists;

/**
 * Relationship
 */
abstract class Relationship
{
    protected string $bindingKey;

    protected string $classAlias;

    protected array $conditions = [];

    protected bool $dependent = false;

    protected string $foreignKey;

    protected string $joinType = 'LEFT';

    protected string $propertyName;

    protected Model $source;

    protected string $strategy = 'select';

    protected Model $target;

    protected array $validStrategies = ['select', 'subquery'];

    /**
     * New relationship constructor.
     *
     * @param ModelRegistry $modelRegistry The ModelRegistry.
     * @param Inflector $inflector The Inflector.
     * @param string $name The relationship name.
     * @param array $options The relationship options.
     *
     * @throws OrmException if the strategy is not valid.
     */
    public function __construct(
        protected ModelRegistry $modelRegistry,
        protected Inflector $inflector,
        protected string $name,
        array $options = []
    ) {
        $defaults = [
            'source',
            'classAlias',
            'propertyName',
            'foreignKey',
            'bindingKey',
            'joinType',
            'conditions',
            'dependent',
        ];

        $options['classAlias'] ??= $this->name;

        foreach ($defaults as $property) {
            if (!array_key_exists($property, $options)) {
                continue;
            }

            $this->$property = $options[$property];
        }

        if (array_key_exists('strategy', $options)) {
            $this->setStrategy($options['strategy']);
        }
    }

    /**
     * Call a method on the target model.
     *
     * @param string $method The method name.
     * @param array $arguments The method arguments.
     * @return mixed The result.
     */
    public function __call(string $method, array $arguments): mixed
    {
        return $this->getTarget()->$method(...$arguments);
    }

    /**
     * Get a Relationship from the target model.
     *
     * @param string $name The property name.
     * @return Relationship The Relationship.
     */
    public function __get(string $name): Relationship
    {
        return $this->getTarget()->$name;
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

        $options['alias'] ??= $target->getAlias();
        $options['sourceAlias'] ??= $source->getAlias();
        $options['type'] ??= $this->joinType;
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
     * @param array|Traversable $entities The entities.
     * @param array $data The find data.
     * @return Collection The related entities.
     */
    public function findRelated(array|Traversable $entities, array $data = []): Collection
    {
        $sourceValues = $this->getRelatedKeyValues($entities);

        if ($sourceValues === []) {
            return Collection::empty();
        }

        $data['conditions'] = array_merge($data['conditions'] ?? [], $this->conditions);

        if (property_exists($this, 'sort') && $this->sort !== null) {
            $data['orderBy'] ??= $this->sort;
        }

        $target = $this->getTarget();

        $query = $target->find($data);

        $this->findRelatedConditions($query, $sourceValues);

        return $query->getResult()->collect();
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
        return $this->foreignKey ??= $this->modelKey(
            $this->source->getClassAlias()
        );
    }

    /**
     * Get the join type.
     *
     * @return string The join type.
     */
    public function getJoinType(): string
    {
        return $this->joinType;
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
        return $this->propertyName ??= $this->propertyName($this->name, $this->hasMultiple());
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
     * Get the select strategy.
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
     *
     * @throws OrmException if the relationship alias is used by another class.
     */
    public function getTarget(): Model
    {
        return $this->target ??= $this->modelRegistry->use($this->name, $this->classAlias);
    }

    /**
     * Determine whether the relationship has multiple related items.
     *
     * @return bool TRUE if the relationship has multiple related items, otherwise FALSE.
     */
    public function hasMultiple(): bool
    {
        return true;
    }

    /**
     * Determine whether the target is dependent.
     *
     * @return bool TRUE if the target is dependent, otherwise FALSE.
     */
    public function isDependent(): bool
    {
        return $this->dependent;
    }

    /**
     * Determine whether the source is the owning side of the relationship.
     *
     * @return bool TRUE if the source is the owning side of the relationship, otherwise FALSE.
     */
    public function isOwningSide(): bool
    {
        return true;
    }

    /**
     * Load related data for entities.
     *
     * @param array|Traversable $entities The entities.
     * @param array $data The find data.
     * @param SelectQuery|null $query The SelectQuery.
     */
    public function loadRelated(array|Traversable $entities, array $data = [], SelectQuery|null $query = null): void
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
            $data['fields'][] = $target->aliasField($targetKey);
        }

        $data['conditions'] = array_merge($data['conditions'] ?? [], $this->conditions);

        if (property_exists($this, 'sort') && $this->sort !== null) {
            $data['orderBy'] ??= $this->sort;
        }

        $data['alias'] = $target->getAlias();

        if ($query) {
            $data = array_merge($query->getOptions(), $data);
        }

        $newQuery = $target->find($data);

        if ($data['strategy'] === 'subquery' && $query) {
            $this->findRelatedSubquery($newQuery, $query);
        } else {
            $this->findRelatedConditions($newQuery, $sourceValues);
        }

        if (array_key_exists('callback', $data) && $data['callback']) {
            $newQuery = $data['callback']($newQuery);
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
     * Save related data for an entity.
     *
     * @param Entity $entity The entity.
     * @return bool TRUE if the save was successful, otherwise FALSE.
     */
    abstract public function saveRelated(Entity $entity): bool;

    /**
     * Set the binding key.
     *
     * @param string $bindingKey The binding key.
     * @return static The Relationship.
     */
    public function setBindingKey(string $bindingKey): static
    {
        $this->bindingKey = $bindingKey;

        return $this;
    }

    /**
     * Set the conditions.
     *
     * @param array $conditions The conditions.
     * @return static The Relationship.
     */
    public function setConditions(array $conditions): static
    {
        $this->conditions = $conditions;

        return $this;
    }

    /**
     * Set whether the target is dependent.
     *
     * @param bool $dependent Whether the target is dependent.
     * @return static The Relationship.
     */
    public function setDependent(bool $dependent): static
    {
        $this->dependent = $dependent;

        return $this;
    }

    /**
     * Set the foreign key.
     *
     * @param string $foreignKey The foreign key.
     * @return static The Relationship.
     */
    public function setForeignKey(string $foreignKey): static
    {
        $this->foreignKey = $foreignKey;

        return $this;
    }

    /**
     * Set the join type.
     *
     * @return string The join type.
     */
    public function setJoinType(string $joinType): static
    {
        $this->joinType = $joinType;

        return $this;
    }

    /**
     * Set the property name.
     *
     * @param string $propertyName The property name.
     * @return static The Relationship.
     */
    public function setProperty(string $propertyName): static
    {
        $this->propertyName = $propertyName;

        return $this;
    }

    /**
     * Set the source Model.
     *
     * @param Model $source The source Model.
     * @return static The Relationship.
     */
    public function setSource(Model $source): static
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Set the select strategy.
     *
     * @param string $strategy The select strategy.
     * @return static The Relationship.
     *
     * @throws OrmException if the strategy is not valid.
     */
    public function setStrategy(string $strategy): static
    {
        if (!in_array($strategy, $this->validStrategies)) {
            throw OrmException::forInvalidStrategy($strategy);
        }

        $this->strategy = $strategy;

        return $this;
    }

    /**
     * Set the target Model.
     *
     * @param Model $target The target Model.
     * @return static The Relationship.
     */
    public function setTarget(Model $target): static
    {
        $this->target = $target;

        return $this;
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
        $relations = $this->findRelated($entities, [
            'conditions' => $options['conditions'] ?? [],
        ]);

        if ($relations->isEmpty()) {
            return true;
        }

        $target = $this->getTarget();

        $foreignKey = $this->getForeignKey();

        if ($this->isDependent() || !$target->getSchema()->column($foreignKey)->isNullable()) {
            if (!$target->deleteMany($relations, $options)) {
                return false;
            }

            return true;
        }

        foreach ($relations as $relation) {
            $relation->set($foreignKey, null, ['temporary' => true]);
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
        $targetKey = $target->aliasField($targetKey);

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
        $targetKey = $target->aliasField($targetKey);

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

        // disable auto alias
        Closure::bind(function(): void { $this->autoAlias = false; }, $query, $query)();

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
     * @param array|Traversable $entities The entities.
     * @return array The related key values.
     */
    protected function getRelatedKeyValues(array|Traversable $entities): array
    {
        if ($this->isOwningSide()) {
            $sourceKey = $this->getBindingKey();
        } else {
            $sourceKey = $this->getForeignKey();
        }

        $sourceValues = [];

        foreach ($entities as $entity) {
            if (!$entity->hasValue($sourceKey)) {
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
    protected function modelKey(string $alias): string
    {
        $alias = $this->inflector->singularize($alias);
        $alias .= 'Id';

        return $this->inflector->underscore($alias);
    }

    /**
     * Get a property name from a model alias.
     *
     * @param string $alias The model alias.
     * @param bool $plural Whether to use a plural name.
     * @return string The property name.
     */
    protected function propertyName(string $alias, bool $plural = false): string
    {
        if (!$plural) {
            $alias = $this->inflector->singularize($alias);
        }

        return $this->inflector->underscore($alias);
    }
}
