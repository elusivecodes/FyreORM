<?php
declare(strict_types=1);

namespace Fyre\ORM\Relationships;

use Fyre\DB\QueryGenerator;
use Fyre\Entity\Entity;
use Fyre\ORM\Exceptions\OrmException;
use Fyre\ORM\ModelRegistry;
use Fyre\Utility\Inflector;

use function array_filter;
use function array_map;
use function in_array;

/**
 * HasMany
 */
class HasMany extends Relationship
{
    protected string $saveStrategy = 'append';

    protected array|string|null $sort = null;

    /**
     * New relationship constructor.
     *
     * @param ModelRegistry $modelRegistry The ModelRegistry.
     * @param Inflector $inflector The Inflector.
     * @param string $name The relationship name.
     * @param array $options The relationship options.
     */
    public function __construct(ModelRegistry $modelRegistry, Inflector $inflector, string $name, array $options = [])
    {
        parent::__construct($modelRegistry, $inflector, $name, $options);

        if (array_key_exists('saveStrategy', $options)) {
            $this->setSaveStrategy($options['saveStrategy']);
        }

        if (array_key_exists('sort', $options)) {
            $this->setSort($options['sort']);
        }
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
     * Save related data for an entity.
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
            $child->saveState();

            if ($child->get($foreignKey) !== $bindingValue) {
                $child->set($foreignKey, null);
                $child->set($foreignKey, $bindingValue);
            }
        }

        if ($this->saveStrategy === 'replace') {
            $preserveConditions = $this->excludeConditions($children);

            if (!$this->unlinkAll([$entity], $options + ['conditions' => $preserveConditions])) {
                return false;
            }
        }

        $options['saveState'] = false;

        if (!$this->getTarget()->saveMany($children, $options)) {
            return false;
        }

        return true;
    }

    /**
     * Set the save strategy.
     *
     * @param string $saveStrategy The save strategy.
     * @return static The HasMany.
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
     * @return static The HasMany.
     */
    public function setSort(array|string|null $sort): static
    {
        $this->sort = $sort;

        return $this;
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
            if ($relation->isNew()) {
                continue;
            }

            $preserveValues[] = $relation->extract($targetKeys);
        }

        if ($preserveValues === []) {
            return [];
        }

        $targetKeys = array_map(
            fn(string $foreignKey): string => $target->aliasField($foreignKey),
            $targetKeys
        );

        return [
            'not' => QueryGenerator::normalizeConditions($targetKeys, $preserveValues),
        ];
    }
}
