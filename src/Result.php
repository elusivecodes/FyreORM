<?php
declare(strict_types=1);

namespace Fyre\ORM;

use ArrayIterator;
use Countable;
use Fyre\DB\ResultSet;
use Fyre\DB\Types\Type;
use Fyre\Entity\Entity;
use Fyre\ORM\Queries\SelectQuery;
use Iterator;
use IteratorAggregate;

use function array_key_exists;
use function array_merge;
use function count;
use function explode;
use function in_array;

/**
 * Result
 */
class Result implements Countable, IteratorAggregate
{

    protected const ENTITY_OPTIONS = [
        'parse' => false,
        'mutate' => false,
        'validate' => false,
        'clean' => true,
        'new' => false
    ];

    protected ResultSet $result;
    protected SelectQuery $query;
    protected bool $eagerLoad = false;

    protected bool $freed = false;

    protected array|null $buffer = null;
    protected array|null $aliasMap = null;

    /**
     * New Result constructor.
     * @param ResultSet $result The ResultSet.
     * @param SelectQuery $query The SelectQuery.
     * @param array $options The result options.
     * @param bool $eagerLoad Whether to eager load the results.
     */
    public function __construct(ResultSet $result, SelectQuery $query, bool $eagerLoad = false)
    {
        $this->result = $result;
        $this->query = $query;
        $this->eagerLoad = $eagerLoad;
    }

    /**
     * Get the results as an array.
     * @return array The results.
     */
    public function all(): array
    {
        if ($this->freed) {
            return [];
        }

        if ($this->eagerLoad) {
            return $this->getBuffer();
        }

        $results = [];

        $count = $this->count();
        for ($i = 0; $i < $count; $i++) {
            $results[] = $this->fetch($i);
        }

        return $results;
    }

    /**
     * Get the column count.
     * @return int The column count.
     */
    public function columnCount(): int
    {
        return $this->result->columnCount();
    }

    /**
     * Get the result columns.
     * @return array The result columns.
     */
    public function columns(): array
    {
        return $this->result->columns();
    }

    /**
     * Get the result count.
     * @return int The result count.
     */
    public function count(): int
    {
        return $this->result->count();
    }

    /**
     * Get a result by index.
     * @param int $index The index.
     * @return Entity|null The result.
     */
    public function fetch(int $index): Entity|null
    {
        if ($this->eagerLoad) {
            return $this->getBuffer()[$index] ?? null;
        }

        if ($this->freed || $index > $this->count() - 1) {
            return null;
        }

        $this->buffer ??= [];

        if (!array_key_exists($index, $this->buffer)) {
            $row = $this->result->fetch($index);
            $data = $this->parseRow($row);

            $this->buffer[$index] = $this->buildEntity($data);
        }

        return $this->buffer[$index];
    }

    /**
     * Get the first result.
     * @return Entity|null The first result.
     */
    public function first(): Entity|null
    {
        return $this->fetch(0);
    }

    /**
     * Free the result from memory.
     */
    public function free(): void
    {
        $this->freed = true;
        $this->buffer = [];
        $this->result->free();
    }

    /**
     * Get the Iterator.
     * @return Iterator The Iterator.
     */
    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->all());
    }

    /**
     * Get a Type class for a column.
     * @param string $name The column name.
     * @return Type|null The Type.
     */
    public function getType(string $name): Type|null
    {
        return $this->result->getType($name);
    }

    /**
     * Get the last result.
     * @return Entity|null The last result.
     */
    public function last(): Entity|null
    {
        return $this->fetch($this->count() - 1);
    }

    /**
     * Build an entity from parsed data.
     * @param array $data The parsed data.
     * @return Entity The Entity.
     */
    protected function buildEntity(array $data): Entity
    {
        $matching = $this->query->getMatching();

        foreach ($matching AS $name => $relationship) {
            $data['_matchingData'][$name] = $relationship->getTarget()
                ->newEntity($data['_matchingData'][$name] ?? [], static::ENTITY_OPTIONS);
        }

        return $this->query->getModel()->newEntity($data, static::ENTITY_OPTIONS);
    }

    /**
     * Get the alias map.
     * @return array The alias map.
     */
    protected function getAliasMap(): array
    {
        if ($this->aliasMap === null) {            
            $this->aliasMap = [$this->query->getAlias() => []];
            static::buildAliasMap($this->aliasMap, $this->query->getContain(), $this->query->getModel());
        }

        return $this->aliasMap;
    }

    /**
     * Get the result buffer.
     * @return array The result buffer.
     */
    protected function getBuffer(): array
    {
        if ($this->buffer !== null) {
            return $this->buffer;
        }

        $rows = $this->result->all();

        $entities = [];

        foreach ($rows AS $row) {
            $data = $this->parseRow($row);

            $entities[] = $this->buildEntity($data);
        }

        $usedAliases = [$this->query->getAlias()];
        static::loadContain($entities, $this->query->getContain(), $this->query->getModel(), $this->query, $usedAliases);

        return $entities;
    }

    /**
     * Parse a result row.
     * @param array $row The row.
     * @return array The parsed data.
     */
    protected function parseRow(array $row): array
    {
        $aliasMap = $this->getAliasMap();
        $matching = $this->query->getMatching();

        $data = [];

        foreach ($row AS $column => $value) {
            $value = $this->getType($column)->fromDatabase($value);

            $parts = explode('__', $column, 2);

            $pointer = &$data;
            if (count($parts) === 2 && (array_key_exists($parts[0], $matching) || array_key_exists($parts[0], $aliasMap))) {
                [$alias, $column] = $parts;

                if (array_key_exists($alias, $matching)) {
                    $data['_matchingData'] ??= [];
                    $data['_matchingData'][$alias] ??= [];
                    $data['_matchingData'][$alias][$column] = $value;
                }

                if (array_key_exists($alias, $aliasMap)) {
                    foreach ($aliasMap[$alias] AS $property) {
                        $pointer[$property] ??= [];
                        $pointer =& $pointer[$property];
                    }
                } else {
                    continue;
                }
            }

            $pointer[$column] = $value;
        }

        return $data;
    }

    /**
     * Build the alias map.
     * @param array $aliasMap The alias map.
     * @param array $contain The contain relationships.
     * @param Model $model The Model.
     * @param array $properties The properties.
     */
    protected static function buildAliasMap(array &$aliasMap, array $contain, Model $model, array $properties = []): void
    {
        foreach ($contain AS $name => $data) {
            $relationship = $model->getRelationship($name);

            $data['strategy'] ??= $relationship->getStrategy();

            if ($data['strategy'] !== 'join' || array_key_exists($name, $aliasMap)) {
                continue;
            }

            $usedAliases[] = $name;
            $property = $relationship->getProperty();

            $aliasMap[$name] = array_merge($properties, [$property]);

            static::buildAliasMap($aliasMap, $data['contain'], $relationship->getTarget(), $aliasMap[$name]);
        }
    }

    /**
     * Load contain relationships for entities.
     * @param array $entities The entities.
     * @param array $contain The contain relationships.
     * @param Model $model The Model.
     * @param SelectQuery $query The Query.
     * @param array $usedAliases The used aliases.
     */
    protected static function loadContain(array $entities, array $contain, Model $model, SelectQuery $query, array &$usedAliases): void
    {
        if ($entities === []) {
            return;
        }

        foreach ($contain AS $name => $data) {
            $relationship = $model->getRelationship($name);

            $data['strategy'] ??= $relationship->getStrategy();

            if ($data['strategy'] !== 'join' || in_array($name, $usedAliases)) {
                $data['type'] ??= $query->getConnectionType();
                $relationship->findRelated($entities, $data, $query);
                continue;
            }

            $usedAliases[] = $name;

            $property = $relationship->getProperty();

            $relations = [];
            foreach ($entities AS $entity) {
                if ($entity->isEmpty($property)) {
                    continue;
                }

                $relations[] = $entity->get($property);
            }

            static::loadContain($relations, $data['contain'], $relationship->getTarget(), $query, $usedAliases);
        }
    }

}
