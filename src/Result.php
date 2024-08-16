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

use function array_fill;
use function array_key_exists;
use function array_key_last;
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
        'new' => false,
    ];

    protected array|null $aliasMap = null;

    protected array|null $buffer = null;

    protected bool $eagerLoad = false;

    protected bool $freed = false;

    protected SelectQuery $query;

    protected ResultSet $result;

    /**
     * New Result constructor.
     *
     * @param ResultSet $result The ResultSet.
     * @param SelectQuery $query The SelectQuery.
     * @param bool $eagerLoad Whether to eager load the results.
     * @param array $options The result options.
     */
    public function __construct(ResultSet $result, SelectQuery $query, bool $eagerLoad = false)
    {
        $this->result = $result;
        $this->query = $query;
        $this->eagerLoad = $eagerLoad;
    }

    /**
     * Get the results as an array.
     *
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
     * Clear the results from the buffer.
     */
    public function clearBuffer(): void
    {
        $this->result->clearBuffer();

        if ($this->buffer !== null) {
            $lastKey = array_key_last($this->buffer);
            $this->buffer = array_fill(0, $lastKey + 1, null);
        }
    }

    /**
     * Get the column count.
     *
     * @return int The column count.
     */
    public function columnCount(): int
    {
        return $this->result->columnCount();
    }

    /**
     * Get the result columns.
     *
     * @return array The result columns.
     */
    public function columns(): array
    {
        return $this->result->columns();
    }

    /**
     * Get the result count.
     *
     * @return int The result count.
     */
    public function count(): int
    {
        return $this->result->count();
    }

    /**
     * Get a result by index.
     *
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

            if ($row === null) {
                $this->buffer[$index] = null;
            } else {
                $data = $this->parseRow($row);

                $this->buffer[$index] = $this->buildEntity($data);
            }
        }

        return $this->buffer[$index];
    }

    /**
     * Get the first result.
     *
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
     *
     * @return Iterator The Iterator.
     */
    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->all());
    }

    /**
     * Get a Type class for a column.
     *
     * @param string $name The column name.
     * @return Type|null The Type.
     */
    public function getType(string $name): Type|null
    {
        return $this->result->getType($name);
    }

    /**
     * Get the last result.
     *
     * @return Entity|null The last result.
     */
    public function last(): Entity|null
    {
        return $this->fetch($this->count() - 1);
    }

    /**
     * Build an entity from parsed data.
     *
     * @param array $data The parsed data.
     * @return Entity The Entity.
     */
    protected function buildEntity(array $data): Entity
    {
        $matching = $this->query->getMatching();

        foreach ($matching as $name => $relationship) {
            $data['_matchingData'][$name] = $relationship->getTarget()
                ->newEntity($data['_matchingData'][$name] ?? [], static::ENTITY_OPTIONS);
        }

        return $this->query->getModel()->newEntity($data, static::ENTITY_OPTIONS);
    }

    /**
     * Get the alias map.
     *
     * @return array The alias map.
     */
    protected function getAliasMap(): array
    {
        if ($this->aliasMap === null) {
            $this->aliasMap = [
                $this->query->getAlias() => [
                    'model' => $this->query->getModel(),
                    'properties' => [],
                ],
            ];
            static::buildAliasMap($this->aliasMap, $this->query->getContain(), $this->query->getModel());
        }

        return $this->aliasMap;
    }

    /**
     * Get the result buffer.
     *
     * @return array The result buffer.
     */
    protected function getBuffer(): array
    {
        if ($this->buffer !== null) {
            return $this->buffer;
        }

        $rows = $this->result->all();

        $this->buffer = [];

        foreach ($rows as $row) {
            $data = $this->parseRow($row);

            $this->buffer[] = $this->buildEntity($data);
        }

        static::loadContain($this->buffer, $this->query->getContain(), $this->query->getModel(), $this->query);

        return $this->buffer;
    }

    /**
     * Parse a result row.
     *
     * @param array $row The row.
     * @return array The parsed data.
     */
    protected function parseRow(array $row): array
    {
        $aliasMap = $this->getAliasMap();
        $matching = $this->query->getMatching();

        $data = [];

        foreach ($row as $column => $value) {
            $schema = null;
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
                    $schema = $aliasMap[$alias]['model']->getSchema();
                    foreach ($aliasMap[$alias]['properties'] as $property) {
                        $pointer[$property] ??= [];
                        $pointer = & $pointer[$property];
                    }
                } else {
                    continue;
                }
            }

            if ($schema && $schema->hasColumn($column)) {
                $type = $schema->getType($column);
            } else {
                $type = $this->getType($column);
            }

            $pointer[$column] = $type->fromDatabase($value);
        }

        return $data;
    }

    /**
     * Build the alias map.
     *
     * @param array $aliasMap The alias map.
     * @param array $contain The contain relationships.
     * @param Model $model The Model.
     * @param array $properties The properties.
     */
    protected static function buildAliasMap(array &$aliasMap, array $contain, Model $model, array $properties = []): void
    {
        foreach ($contain as $name => $data) {
            $relationship = $model->getRelationship($name);

            $data['strategy'] ??= $relationship->getStrategy();

            if ($data['strategy'] !== 'join' || array_key_exists($name, $aliasMap)) {
                continue;
            }

            $property = $relationship->getProperty();

            $aliasMap[$name] = [
                'model' => $relationship->getTarget(),
                'properties' => array_merge($properties, [$property]),
            ];

            static::buildAliasMap($aliasMap, $data['contain'], $relationship->getTarget(), $aliasMap[$name]['properties']);
        }
    }

    /**
     * Load contain relationships for entities.
     *
     * @param array $entities The entities.
     * @param array $contain The contain relationships.
     * @param Model $model The Model.
     * @param SelectQuery $query The Query.
     * @param string $pathPrefix The path prefix.
     */
    protected static function loadContain(array $entities, array $contain, Model $model, SelectQuery $query, string $pathPrefix = ''): void
    {
        if ($entities === []) {
            return;
        }

        $eagerLoadPaths = $query->getEagerLoadPaths();

        foreach ($contain as $name => $data) {
            $path = $pathPrefix.'.'.$name;

            $relationship = $model->getRelationship($name);

            $data['strategy'] ??= $relationship->getStrategy();

            if ($data['strategy'] !== 'join' || in_array($path, $eagerLoadPaths)) {
                $data['type'] ??= $query->getConnectionType();
                $relationship->findRelated($entities, $data, $query);

                continue;
            }

            $property = $relationship->getProperty();

            $relations = [];
            foreach ($entities as $entity) {
                if ($entity->isEmpty($property)) {
                    continue;
                }

                $relations[] = $entity->get($property);
            }

            static::loadContain($relations, $data['contain'], $relationship->getTarget(), $query, $path);
        }
    }
}
