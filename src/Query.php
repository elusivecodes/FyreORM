<?php
declare(strict_types=1);

namespace Fyre\ORM;

use
    Fyre\DB\Connection,
    Fyre\DB\QueryBuilder,
    Fyre\DB\ResultSet,
    Fyre\Entity\Entity,
    Fyre\ORM\Exceptions\OrmException,
    Fyre\ORM\Relationships\Relationship;

use function
    array_key_exists,
    array_map,
    array_merge,
    count,
    explode,
    in_array,
    is_numeric,
    is_string,
    str_replace;

/**
 * Query
 */
class Query extends QueryBuilder
{

    protected Result|bool|null $result = null;

    protected Model $model;

    protected string $alias;

    protected string $connectionType;

    protected bool $subquery;

    protected array $contain = [];

    protected array $containJoin = [];

    protected array $matching = [];

    protected bool|null $autoFields = null;

    protected bool $eagerLoad = false;

    /**
     * New Query constructor.
     * @param Model $model The Model.
     * @param array $options The Query options.
     */
    public function __construct(Model $model, array $options = [])
    {
        $this->model = $model;
        $this->alias = $options['alias'] ?? $this->model->getAlias();
        $this->connectionType = $options['type'] ?? Model::WRITE;
        $this->subquery = $options['subquery'] ?? false;

        parent::__construct($this->model->getConnection($this->connectionType));
    }

    /**
     * Get the results as an array.
     * @return array The results.
     */
    public function all(): array
    {
        return $this->getResult()->all();
    }

    /**
     * Clear the buffered result.
     * @return Query The Query.
     */
    public function clearResult(): static
    {
        $this->result = null;

        return $this;
    }

    /**
     * Set the contain relationships.
     * @param string|array $contain The contain relationships.
     * @return Query The Query.
     */
    public function contain(string|array $contain): static
    {
        $contain = Model::normalizeContain($contain, $this->model);

        $this->contain = Model::mergeContain($this->contain, $contain['contain'] ?? []);

        return $this;
    }

    /**
     * Get the result count.
     * @return int The result count.
     */
    public function count(): int
    {
        return $this->getResult()->count();
    }

    /**
     * Enable or disable auto fields.
     * @param bool Whether to enable or disable auto fields.
     * @return Query The Query.
     */
    public function enableAutoFields(bool $autoFields): static
    {
        $this->autoFields = $autoFields;

        return $this;
    }

    /**
     * Get the first result.
     * @return Entity|null The first result.
     */
    public function first(): Entity|null
    {
        return $this->limit(1)->getResult()->first();
    }

    /**
     * Get the alias.
     * @return string The alias.
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * Get the connection type.
     * @return string The connection type.
     */
    public function getConnectionType(): string
    {
        return $this->connectionType;
    }

    /**
     * Get the contain array.
     * @return array The contain array.
     */
    public function getContain(): array
    {
        return $this->contain;
    }

    /**
     * Get the matching array.
     * @return array The matching array.
     */
    public function getMatching(): array
    {
        return $this->matching;
    }

    /**
     * Get the Model.
     * @return Model The Model.
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * Get the query result.
     * @return Result|bool The query result.
     */
    public function getResult(): Result|bool
    {
        if ($this->result === null) {
            $result = $this->execute();

            if ($result instanceof ResultSet) {
                $result = new Result($result, $this, $this->eagerLoad);

                $this->result = $this->model->afterFind($result);
            } else {
                $this->result = $result;
            }
        }

        return $this->result; 
    }

    /**
     * INNER JOIN a relationship table.
     * @param string $contain The contain string.
     * @param array $conditions The JOIN conditions.
     * @return Query The Query.
     */
    public function innerJoinWith(string $contain, array $conditions = []): static
    {
        return $this->containJoin($contain, $conditions, 'INNER');
    }

    /**
     * LEFT JOIN a relationship table.
     * @param string $contain The contain string.
     * @param array $conditions The JOIN conditions.
     * @return Query The Query.
     */
    public function leftJoinWith(string $contain, array $conditions = []): static
    {
        return $this->containJoin($contain, $conditions);
    }

    /**
     * INNER JOIN a relationship table and load matching data.
     * @param string $contain The contain string.
     * @param array $conditions The JOIN conditions.
     * @return Query The Query.
     */
    public function matching(string $contain, array $conditions = []): static
    {
        return $this->containJoin($contain, $conditions, 'INNER', true);
    }

    /**
     * LEFT JOIN a relationship table and exclude matching rows.
     * @param string $contain The contain string.
     * @param array $conditions The JOIN conditions.
     * @return Query The Query.
     */
    public function notMatching(string $contain, array $conditions = []): static
    {
        return $this->containJoin($contain, $conditions, 'LEFT', false);
    }

    /**
     * Set the SELECT fields.
     * @param string|array $fields The fields.
     * @return QueryBuilder The QueryBuilder.
     */
    public function select(string|array $fields = '*'): static
    {
        $this->autoFields ??= false;
        $this->addFields((array) $fields, $this->model, $this->alias);

        return $this;
    }

    /**
     * Generate the SQL query.
     * @return string The SQL query.
     */
    public function sql(): string
    {
        $joins = $this->joins;
        $fields = $this->fields;

        $this->joins = [];
        $this->fields = [];

        $usedAliases = [$this->alias];

        switch ($this->action) {
            case 'insert':
            case 'insertBatch':
            case 'replace':
            case 'replaceBatch':
                $this->tables = [$this->model->getTable()];
                break;
            default:
                $this->tables = [
                    $this->alias => $this->model->getTable()
                ];
                break;
        }

        switch ($this->action) {
            case 'select':
                if ($this->autoFields !== false) {
                    $this->autoFields($this->model, $this->alias);
                } else {
                    $this->addFields($this->model->getPrimaryKey(), $this->model, $this->alias);
                }

                $this->fields += $fields;

                $this->containAll($this->contain, $this->model, $this->alias, $usedAliases);

                foreach ($this->matching AS $name => $relationship) {
                    $target = $relationship->getTarget();

                    if ($this->autoFields !== false) {
                        $this->autoFields($target, $name);
                    } else {
                        $this->addFields($target->getPrimaryKey(), $target, $name);
                    }
                }
                break;
            case 'delete':
                $this->deleteAliases = [$this->alias];
                break;
        }

        $this->joins += $this->containJoin;

        foreach ($joins AS $alias => $join) {
            if (is_numeric($alias)) {
                $alias = $join['alias'] ?? $join['table'] ?? null;
            }

            if (!$alias) {
                continue;
            }

            unset($join['alias']);

            $this->joins[$alias] ??= $join;
        }

        $sql = parent::sql();

        $this->joins = $joins;
        $this->fields = $fields;

        return $sql;
    }

    /**
     * Set query as an UPDATE.
     * @param array $data The data.
     * @return QueryBuilder The QueryBuilder.
     */
    public function update(array $data): static
    {
        $this->action = 'update';

        $this->data = [];
        foreach ($data AS $field => $value) {
            $field = $this->model->aliasField($field, $this->alias);
            $this->data[$field] = $value;
        }

        return $this;
    }

    /**
     * Set query as a batch UPDATE.
     * @param array $data The data.
     * @param string|array $updateKeys The key to use for updating.
     * @return QueryBuilder The QueryBuilder.
     */
    public function updateBatch(array $data, string|array $updateKeys): static
    {
        $this->action = 'updateBatch';

        $this->updateKeys = array_map(
            fn(string $field): string => $this->model->aliasField($field, $this->alias),
            $updateKeys
        );

        foreach ($data AS $index => $values) {
            $this->data[$index] = [];
            foreach ($values AS $field => $value) {
                $field = $this->model->aliasField($field, $this->alias);
                $this->data[$index][$field] = $value;
            }
        }

        return $this;
    }

    /**
     * Add SELECT fields.
     * @param array $fields The fields to add.
     * @param Model $model The Model.
     * @param string $alias The table alias.
     * @param bool $overwrite Whether to overwrite existing fields.
     */
    protected function addFields(array $fields, Model $model, string $alias, bool $overwrite = true): void
    {
        foreach ($fields AS $name => $field) {
            if ($field === '*') {
                $this->autoFields($model, $alias);
                continue;
            }

            if (is_string($field)) {
                $field = $model->aliasField($field, $alias);
            }

            if ($this->subquery) {
                $this->fields[] = $field;
                continue;
            }

            if (is_numeric($name)) {
                $name = str_replace('.', '__', $field);
            }

            if (!$overwrite && array_key_exists($name, $this->fields)) {
                continue;
            }

            $this->fields[$name] = $field;
        }
    }

    /**
     * Automatically add SELECT fields from a Model schema.
     * @param Model $model The Model.
     * @param string $alias The table alias.
     */
    protected function autoFields(Model $model, string $alias): void
    {
        $columns = $model->getSchema(Model::READ)->columnNames();
    
        $this->addFields($columns, $model, $alias, false);
    }

    /**
     * Add contain relationships to query.
     * @param array $contain The contain relationships.
     * @param Model $model The Model.
     * @param string $alias The table alias.
     * @param array $usedAliases The used aliases.
     */
    protected function containAll(array $contain, Model $model, string $alias, array &$usedAliases): void
    {
        foreach ($contain AS $name => $data) {
            $relationship = $model->getRelationship($name);

            $data['strategy'] ??= $relationship->getStrategy();

            if ($data['strategy'] !== 'join' || in_array($name, $usedAliases)) {
                $bindingKey = $relationship->getBindingKey();
                $this->addFields([$bindingKey], $model, $alias);
                $this->eagerLoad = true;
                continue;
            }

            $data['autoFields'] ??= $this->autoFields;

            $target = $relationship->getTarget();

            $joins = $relationship->buildJoins([
                'alias' => $name,
                'sourceAlias' => $alias,
                'conditions' => $data['conditions'] ?? []
            ]);

            foreach ($joins AS $joinAlias => $join) {
                $this->joins[$joinAlias] ??= $join;
                $usedAliases[] = $joinAlias;
            }

            if (array_key_exists('fields', $data)) {
                $this->addFields($data['fields'], $target, $name);
            } else if ($data['autoFields'] !== false) {
                $this->autoFields($target, $name);
            } else {
                $this->addFields($target->getPrimaryKey(), $target, $name);
            }

            $this->containAll($data['contain'], $target, $name, $usedAliases);
        }
    }

    /**
     * Add a relationship JOIN.
     * @param string $contain The contain string.
     * @param array $conditions The JOIN conditions.
     * @param string $type The JOIN type.
     * @param bool|null $matching Whether this is a matching/noMatching join.
     * @return Query The Query.
     */
    protected function containJoin(string $contain, array $conditions, string $type = 'LEFT', bool|null $matching = null): static
    {
        $contain = explode('.', $contain);
        $lastContain = count($contain) - 1;

        $model = $this->model;
        $sourceAlias = $this->alias;

        foreach ($contain AS $i => $alias) {
            $isLastJoin = $i === $lastContain;

            $relationship = $model->getRelationship($alias);

            if (!$relationship) {
                throw OrmException::forInvalidRelationship($alias);
            }

            $model = $relationship->getTarget();

            $joins = $relationship->buildJoins([
                'alias' => $alias,
                'sourceAlias' => $sourceAlias,
                'conditions' => $isLastJoin ?
                    $conditions :
                    [],
                'type' => $type
            ]);

            foreach ($joins AS $joinAlias => $join) {
                if ($isLastJoin) {
                    $this->containJoin[$joinAlias] = $join;
                } else {
                    $this->containJoin[$joinAlias] ??= $join;
                }
            }

            if ($isLastJoin) {
                if ($matching === true) {
                    $this->matching[$alias] = $relationship;
                } else if ($matching === false) {
                    $matchingConditions = array_map(
                        fn(string $key): string => $model->aliasField($key, $alias).' IS NULL',
                        $model->getPrimaryKey()
                    );

                    $this->where($matchingConditions);
                }
            }

            $sourceAlias = $alias;
        }

        return $this;
    }

}
