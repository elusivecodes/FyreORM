<?php
declare(strict_types=1);

namespace Fyre\ORM;

use
    ArrayIterator,
    Countable,
    Fyre\DB\ResultSet,
    Fyre\DB\Types\Type,
    Fyre\Entity\Entity,
    Iterator,
    IteratorAggregate;

use function
    array_key_exists,
    array_merge,
    count,
    explode,
    in_array;

/**
 * Result
 */
class Result implements Countable, IteratorAggregate
{

    protected ResultSet $result;

    protected Query $query;

    protected array|null $buffer = null;

    protected array $usedAliases = [];

    /**
     * New Result constructor.
     * @param ResultSet $result The ResultSet.
     * @param Query $query The Query.
     */
    public function __construct(ResultSet $result, Query $query)
    {
        $this->result = $result;
        $this->query = $query;
    }

    /**
     * Get the results as an array.
     * @return array The results.
     */
    public function all(): array
    {
        return $this->buffer ??= $this->getBuffer();
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
        return $this->all()[$index] ?? null;
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
        $this->buffer = null;
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
     * Get the result buffer.
     * @return array The result buffer.
     */
    protected function getBuffer(): array
    {
        if ($this->buffer !== null) {
            return $this->buffer;
        }

        $rows = $this->result->all();

        $alias = $this->query->getAlias();
        $contain = $this->query->getContain();
        $matching = $this->query->getMatching();
        $model = $this->query->getModel();
        $type = $this->query->getType();

        $matchingName = null;
        if ($matching) {
            $matchingName = $matching->getName();
            $matchingModel = $matching->getTarget();
        }

        $aliasMap = [$alias => []];
        $usedAliases = [$alias];

        static::buildAliasMap($aliasMap, $contain, $model);

        $entityOptions = [
            'parse' => false,
            'validate' => false,
            'clean' => true,
            'new' => false
        ];

        $entities = [];

        foreach ($rows AS $row) {
            $data = $this->parseRow($row, $aliasMap, $matchingName);

            if ($matching) {
                $data['_matchData'] = $matchingModel->newEntity($data['_matchData'] ?? [], $entityOptions);
            }

            $entities[] = $model->newEntity($data, $entityOptions);
        }

        static::loadContain($entities, $contain, $model, $type, $usedAliases);

        return $entities;
    }

    /**
     * Parse a result row.
     * @param array $row The row.
     * @param array $aliasMap The alias map.
     * @return array The parsed data.
     */
    protected function parseRow(array $row, array $aliasMap, string|null $matchingAlias): array
    {
        $data = [];

        foreach ($row AS $column => $value) {
            $value = $this->getType($column)->fromDatabase($value);

            $parts = explode('__', $column, 2);

            $pointer = &$data;
            if (count($parts) === 2 && ($parts[0] === $matchingAlias || array_key_exists($parts[0], $aliasMap))) {
                [$alias, $column] = $parts;

                if ($alias === $matchingAlias) {
                    $data['_matchData'] ??= [];
                    $data['_matchData'][$column] = $value;
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

            if (!$relationship->canBeJoined() || array_key_exists($name, $aliasMap)) {
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
     * @param string $type The connection type.
     * @param array $usedAliases The used aliases.
     */
    protected static function loadContain(array $entities, array $contain, Model $model, string $type, array &$usedAliases): void
    {
        if ($entities === []) {
            return;
        }

        foreach ($contain AS $name => $data) {
            $relationship = $model->getRelationship($name);

            if (!$relationship->canBeJoined() || in_array($name, $usedAliases)) {
                $relationship->findRelated($entities, $data + ['type' => $type]);
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

            static::loadContain($relations, $data['contain'], $relationship->getTarget(), $type, $usedAliases);
        }
    }

}
