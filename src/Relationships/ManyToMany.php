<?php
declare(strict_types=1);

namespace Fyre\ORM\Relationships;

use Fyre\Collection\Collection;
use Fyre\Container\Container;
use Fyre\Entity\Entity;
use Fyre\ORM\Exceptions\OrmException;
use Fyre\ORM\Model;
use Fyre\ORM\ModelRegistry;
use Fyre\ORM\Queries\SelectQuery;
use Fyre\Utility\Inflector;
use Traversable;

use function array_key_exists;
use function array_merge;
use function implode;
use function in_array;
use function is_array;
use function natsort;

/**
 * ManyToMany
 */
class ManyToMany extends Relationship
{
    protected Container $container;

    protected Model|null $junction = null;

    protected string $saveStrategy = 'replace';

    protected array|string|null $sort = null;

    protected HasMany|null $sourceRelationship = null;

    protected string $targetForeignKey;

    protected BelongsTo|null $targetRelationship = null;

    protected string $through;

    /**
     * New relationship constructor.
     *
     * @param Container $container The Container.
     * @param ModelRegistry $modelRegistry The ModelRegistry.
     * @param Inflector $inflector The Inflector.
     * @param string $name The relationship name.
     * @param array $options The relationship options.
     */
    public function __construct(Container $container, ModelRegistry $modelRegistry, Inflector $inflector, string $name, array $options = [])
    {
        parent::__construct($modelRegistry, $inflector, $name, $options);

        $this->container = $container;

        if (!array_key_exists('through', $options)) {
            $aliases = [
                $this->source->getClassAlias(),
                $this->name,
            ];

            natsort($aliases);

            $options['through'] = implode('', $aliases);
        }

        $this->through = $options['through'];

        if (array_key_exists('saveStrategy', $options)) {
            $this->setSaveStrategy($options['saveStrategy']);
        }

        if (array_key_exists('sort', $options)) {
            $this->setSort($options['sort']);
        }

        if (array_key_exists('targetForeignKey', $options)) {
            $this->setTargetForeignKey($options['targetForeignKey']);
        }
    }

    /**
     * Build join data.
     *
     * @param array $options The join options.
     * @return array The join data.
     */
    public function buildJoins(array $options = []): array
    {
        $sourceJoins = $this->getSourceRelationship()->buildJoins([
            'sourceAlias' => $options['sourceAlias'] ?? null,
            'type' => $options['type'] ?? null,
        ]);

        $targetJoins = $this->getTargetRelationship()->buildJoins([
            'alias' => $options['alias'] ?? null,
            'type' => $options['type'] ?? null,
            'conditions' => $options['conditions'] ?? null,
        ]);

        return array_merge($sourceJoins, $targetJoins);
    }

    /**
     * Find related data for entities.
     *
     * @param array|Traversable $entities The entities.
     * @param array $data The find data.
     * @return Collection The related entities.
     */
    public function findRelated(array|Traversable $entities, array $options = []): Collection
    {
        $targetRelationship = $this->getTargetRelationship();
        $joinProperty = $targetRelationship->getProperty();
        $targetName = $targetRelationship->getName();

        $contain = $options['contain'] ?? [];
        $options['contain'] = [$targetName => $contain];

        if ($this->sort !== null) {
            $options['orderBy'] ??= $this->sort;
        }

        return $this->getSourceRelationship()
            ->findRelated($entities, $options)
            ->map(function(Entity $child) use ($joinProperty): Entity {
                $realChild = $child->get($joinProperty);
                $child->unset($joinProperty);

                $realChild->set('_joinData', $child);
                $realChild->setDirty('_joinData', false);

                return $realChild;
            });
    }

    /**
     * Get the junction Model.
     *
     * @return Model The junction Model.
     */
    public function getJunction(): Model
    {
        return $this->junction ??= $this->modelRegistry->use($this->through);
    }

    /**
     * Get the save strategy.
     *
     * @return string The save strategy.
     */
    public function getSaveStrategy(): string
    {
        return $this->saveStrategy;
    }

    /**
     * Get the sort order.
     *
     * @return array|string|null The sort order.
     */
    public function getSort(): array|string|null
    {
        return $this->sort;
    }

    /**
     * Get the source relationship.
     *
     * @return HasMany The source relationship.
     */
    public function getSourceRelationship(): HasMany
    {
        return $this->sourceRelationship ??= $this->container->build(HasMany::class, [
            'name' => $this->getJunction()->getAlias(),
            'options' => [
                'source' => $this->getSource(),
                'foreignKey' => $this->getForeignKey(),
                'bindingKey' => $this->getBindingKey(),
                'dependent' => true,
            ],
        ]);
    }

    /**
     * Get the target foreign key.
     *
     * @return string The target foreign key.
     */
    public function getTargetForeignKey(): string
    {
        return $this->targetForeignKey ??= $this->modelKey($this->name);
    }

    /**
     * Get the target relationship.
     *
     * @return BelongsTo The target relationship.
     */
    public function getTargetRelationship(): BelongsTo
    {
        if ($this->targetRelationship) {
            return $this->targetRelationship;
        }

        $junction = $this->getJunction();

        $this->targetRelationship = $this->container->build(BelongsTo::class, [
            'name' => $this->name,
            'options' => [
                'source' => $junction,
                'classAlias' => $this->classAlias,
                'foreignKey' => $this->getTargetForeignKey(),
            ],
        ]);

        if (!$junction->hasRelationship($this->name)) {
            $junction->addRelationship($this->targetRelationship);
        }

        return $this->targetRelationship;
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

        if ($sourceValues === []) {
            foreach ($entities as $entity) {
                $entity->set($property, []);
                $entity->setDirty($property, false);
            }

            return;
        }

        $data['strategy'] ??= $this->getStrategy();
        $junction = $this->getJunction();
        $bindingKey = $this->getBindingKey();
        $foreignKey = $this->getForeignKey();
        $targetRelationship = $this->getTargetRelationship();
        $joinProperty = $targetRelationship->getProperty();
        $targetName = $targetRelationship->getName();

        if (array_key_exists('fields', $data) || (array_key_exists('autoFields', $data) && !$data['autoFields'])) {
            $data['fields'] ??= [];
            $data['fields'][] = $junction->aliasField($foreignKey);
        }

        $contain = $data['contain'];
        $data['contain'] = [$targetName => $contain];

        if ($this->sort !== null) {
            $data['orderBy'] ??= $this->sort;
        }

        if ($query) {
            $data = array_merge($query->getOptions(), $data);
        }

        $newQuery = $junction->find($data);

        if ($data['strategy'] === 'subquery' && $query) {
            $this->findRelatedSubquery($newQuery, $query);
        } else {
            $this->findRelatedConditions($newQuery, $sourceValues);
        }

        if (array_key_exists('callback', $data) && $data['callback']) {
            $newQuery = $data['callback']($newQuery);
        }

        $allChildren = $newQuery
            ->getResult()
            ->map(function(Entity $child) use ($joinProperty): Entity {
                $realChild = $child->get($joinProperty);
                $child->unset($joinProperty);

                $realChild->set('_joinData', $child);
                $realChild->setDirty('_joinData', false);

                return $realChild;
            })
            ->toArray();

        foreach ($entities as $entity) {
            $bindingValue = $entity->get($bindingKey);

            $children = [];
            foreach ($allChildren as $child) {
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
     * Save related data for an entity.
     *
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

        if ($this->saveStrategy === 'replace') {
            if (!$this->getSourceRelationship()->unlinkAll([$entity], $options)) {
                return false;
            }
        }

        if ($relations === []) {
            return true;
        }

        foreach ($relations as $relation) {
            $relation->saveState();
        }

        $target = $this->getTarget();

        $options['saveState'] = false;

        if (!$target->saveMany($relations, $options)) {
            return false;
        }

        $junction = $this->getJunction();
        $bindingKey = $this->getBindingKey();
        $foreignKey = $this->getForeignKey();
        $targetRelationship = $this->getTargetRelationship();
        $targetBindingKey = $targetRelationship->getBindingKey();
        $targetForeignKey = $targetRelationship->getForeignKey();
        $bindingValue = $entity->get($bindingKey);

        $joinEntities = [];
        foreach ($relations as $relation) {
            $joinData = $relation->get('_joinData') ?? [];

            if ($joinData instanceof Entity) {
                $joinEntity = $joinData;
            } else if (is_array($joinData)) {
                $joinEntity = $junction->newEntity($joinData);
            } else {
                $joinEntity = $junction->newEmptyEntity();
            }

            $targetBindingValue = $relation->get($targetBindingKey);

            $joinEntity->set($foreignKey, $bindingValue);
            $joinEntity->set($targetForeignKey, $targetBindingValue);

            $joinEntities[] = $joinEntity;
        }

        if (!$junction->saveMany($joinEntities, $options)) {
            return false;
        }

        return true;
    }

    /**
     * GetSet the junction Model.
     *
     * @param Model $junction The junction Model.
     */
    public function setJunction(Model $junction): static
    {
        $this->junction = $junction;

        return $this;
    }

    /**
     * Set the save strategy.
     *
     * @param string $saveStrategy The save strategy.
     * @return static The ManyToMany.
     *
     * @throws OrmException if the strategy is not valid.
     */
    public function setSaveStrategy(string $saveStrategy): static
    {
        if (!in_array($saveStrategy, ['append', 'replace'])) {
            throw OrmException::forInvalidSaveStrategy($saveStrategy);
        }

        $this->saveStrategy = $saveStrategy;

        return $this;
    }

    /**
     * Set the sort order.
     *
     * @param array|string|null $sort The sort order.
     * @return static The ManyToMany.
     */
    public function setSort(array|string|null $sort): static
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Set the target foreign key.
     *
     * @param string $targetForeignKey The target foreign key.
     * @return static The ManyToMany.
     */
    public function setTargetForeignKey(string $targetForeignKey): static
    {
        $this->targetForeignKey = $targetForeignKey;

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
        return $this->getSourceRelationship()->unlinkAll($entities, $options);
    }
}
