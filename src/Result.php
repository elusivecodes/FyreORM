<?php
declare(strict_types=1);

namespace Fyre\ORM;

use Closure;
use Countable;
use Fyre\Collection\Collection;
use Fyre\DB\ResultSet;
use Fyre\DB\Types\Type;
use Fyre\Entity\Entity;
use Fyre\ORM\Queries\SelectQuery;
use Fyre\Utility\Traits\MacroTrait;
use Generator;
use Iterator;
use IteratorAggregate;
use JsonSerializable;

use function array_key_exists;
use function array_merge;
use function count;
use function explode;
use function in_array;

/**
 * Result
 */
class Result implements Countable, IteratorAggregate, JsonSerializable
{
    use MacroTrait {
        __call as protected macroCall;
    }

    protected const ENTITY_OPTIONS = [
        'guard' => false,
        'mutate' => false,
        'parse' => false,
        'validate' => false,
        'clean' => true,
        'new' => false,
    ];

    protected array|null $aliasMap = null;

    protected Collection $collection;

    protected bool $freed = false;

    /**
     * New Result constructor.
     *
     * @param ResultSet $result The ResultSet.
     * @param SelectQuery $query The SelectQuery.
     * @param bool $buffer Whether to buffer the results.
     * @param array $options The result options.
     */
    public function __construct(
        protected ResultSet $result,
        protected SelectQuery $query,
        bool $buffer = true
    ) {
        $eagerLoad = $this->query->getEagerLoadPaths() !== [];

        $this->collection = new Collection(function() use ($eagerLoad, $buffer): Generator {
            $resultBuffer = null;

            while ($this->result->valid()) {
                if ($this->freed) {
                    break;
                }

                $row = $this->result->current();

                if ($row === null) {
                    yield null;

                    $this->result->next();

                    continue;
                }

                $data = $this->parseRow($row);

                $entity = $this->buildEntity($data);

                if ($eagerLoad && !$buffer) {
                    static::loadContain([$entity], $this->query->getContain(), $this->query->getModel(), $this->query);
                }

                if ($resultBuffer === null) {
                    $resultBuffer = & Closure::bind(fn&(): array => $this->buffer, $this->result, $this->result)();
                }

                $resultBuffer[$this->result->key()] = null;

                yield $entity;

                $this->result->next();
            }

            $this->free();
        });

        if ($buffer) {
            $this->collection = $this->collection->cache();
        }

        if ($eagerLoad && $buffer) {
            static::loadContain($this->collection->toArray(), $this->query->getContain(), $this->query->getModel(), $this->query);
        }
    }

    /**
     * Call a Collection method.
     *
     * @param string $method The method.
     * @param array $arguments Arguments to pass to the method.
     * @return mixed The return value.
     */
    public function __call($method, $arguments = []): mixed
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $arguments);
        }

        return $this->collection->$method(...$arguments);
    }

    /**
     * Convert the collection to a JSON encoded string.
     *
     * @return string The JSON encoded string.
     */
    public function __toString(): string
    {
        return (string) $this->collection;
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
        foreach ($this as $key => $entity) {
            if ($key === $index) {
                return $entity;
            }
        }

        return null;
    }

    /**
     * Free the result from memory.
     */
    public function free(): void
    {
        if (!$this->freed) {
            $this->freed = true;
            $this->result->free();
        }
    }

    /**
     * Get the collection Iterator.
     *
     * @return Iterator The collection Iterator.
     */
    public function getIterator(): Iterator
    {
        return $this->collection->getIterator();
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
     * Convert the collection to an array for JSON serializing.
     *
     * @return array The array for serializing.
     */
    public function jsonSerialize(): array
    {
        return $this->collection->jsonSerialize();
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
                $type = $schema->column($column)->type();
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
                $data['connectionType'] ??= $query->getConnectionType();
                $relationship->loadRelated($entities, $data, $query);

                continue;
            }

            $property = $relationship->getProperty();

            $relations = [];
            foreach ($entities as $entity) {
                if (!$entity->hasValue($property)) {
                    continue;
                }

                $relations[] = $entity->get($property);
            }

            static::loadContain($relations, $data['contain'], $relationship->getTarget(), $query, $path);
        }
    }
}
