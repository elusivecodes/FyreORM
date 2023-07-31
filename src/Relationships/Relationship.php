<?php
declare(strict_types=1);

namespace Fyre\ORM\Relationships;

use Fyre\ORM\Exceptions\OrmException;
use Fyre\ORM\Model;
use Fyre\ORM\ModelRegistry;
use Fyre\ORM\Query;
use Fyre\Utility\Inflector;

use function array_key_exists;
use function array_merge;
use function count;
use function in_array;
use function str_replace;

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

    protected string $strategy = 'select';

    protected array $validStrategies = ['select', 'subquery'];

    protected array $conditions = [];

    protected bool $dependent = false;

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
            'strategy',
            'conditions',
            'dependent'
        ];

        foreach ($defaults AS $property) {
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
     * Find related data for entities.
     * @param array $entities The entities.
     * @param array $data The find data.
     * @param Query|null $query The Query.
     */
    public function findRelated(array $entities, array $data, Query|null $query = null): void
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

        $target = $this->getTarget();
        $property = $this->getProperty();
        $hasMultiple = $this->hasMultiple();

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

        $data['conditions'] = array_merge($conditions, $data['conditions'] ?? []);

        $allChildren = $target->find($data)->all();

        foreach ($entities AS $entity) {
            $sourceValue = $entity->get($sourceKey);

            $children = [];
            foreach ($allChildren AS $child) {
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
        return $this->propertyName ??= static::propertyName($this->name, $this->hasMultiple());
    }

    /**
     * Get the strategy.
     * @return string The strategy.
     */
    public function getStrategy(): string
    {
        return $this->strategy;
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
     * Determine if the relationship has multiple related items.
     * @return bool TRUE if the relationship has multiple related items, otherwise FALSE.
     */
    public function hasMultiple(): bool
    {
        return true;
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
     * Get the subquery contain conditions for a Query.
     * @param Query $query The Query.
     * @return array The subquery contain conditions.
     */
    protected function containConditionSubquery(Query $query): array
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

        $containConditions = [
            $targetKey.' IN' => $query->getModel()
                ->getConnection()
                ->builder()
                ->table([
                    $alias => $query
                ])
                ->select([
                    str_replace('.', '__', $sourceKey)
                ])
        ];

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
