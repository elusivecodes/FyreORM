<?php
declare(strict_types=1);

namespace Fyre\ORM\Queries;

use Fyre\DB\ResultSet;
use Fyre\DB\ValueBinder;
use Fyre\Entity\Entity;
use Fyre\ORM\Exceptions\OrmException;
use Fyre\ORM\Model;
use Fyre\ORM\Queries\Traits\ModelTrait;
use Fyre\ORM\Result;

use function array_key_exists;
use function array_map;
use function count;
use function explode;
use function in_array;
use function is_numeric;
use function is_string;
use function str_replace;

/**
 * SelectQuery
 */
class SelectQuery extends \Fyre\DB\Queries\SelectQuery
{

    protected Result|bool|null $result = null;

    protected string $alias;
    protected string $connectionType;
    protected bool $subquery;

    protected array $contain = [];
    protected array $containJoin = [];
    protected array $matching = [];

    protected bool|null $autoFields = null;
    protected bool $eagerLoad = false;

    protected array|null $originalFields = null;
    protected array|null $originalJoins = null;
    protected bool $prepared = false;

    protected int|null $count = null;

    protected bool $beforeFindTriggered = false;

    use ModelTrait;

    /**
     * New SelectQuery constructor.
     * @param Model $model The Model.
     * @param array $options The SelectQuery options.
     */
    public function __construct(Model $model, array $options = [])
    {
        $this->model = $model;
        $this->alias = $options['alias'] ?? $this->model->getAlias();
        $this->connectionType = $options['type'] ?? Model::READ;
        $this->subquery = $options['subquery'] ?? false;

        parent::__construct($this->model->getConnection($this->connectionType), []);

        $this->from([
            $this->alias => $this->model->getTable()
        ]);
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
     * @return SelectQuery The SelectQuery.
     */
    public function clearResult(): static
    {
        $this->result = null;

        return $this;
    }

    /**
     * Set the contain relationships.
     * @param string|array $contain The contain relationships.
     * @return SelectQuery The SelectQuery.
     */
    public function contain(string|array $contain): static
    {
        $contain = Model::normalizeContain($contain, $this->model);

        $this->contain = Model::mergeContain($this->contain, $contain['contain'] ?? []);

        $this->dirty();

        return $this;
    }

    /**
     * Get the result count.
     * @return int The result count.
     */
    public function count(): int
    {
        if ($this->count === null) {
            $query = clone $this;

            if (!$this->beforeFindTriggered) {
                $this->model->handleEvent('beforeFind', $query);
            }

            $this->count = $query->getConnection()
                ->select([
                    'count' => 'COUNT(*)'
                ])
                ->from([
                    'count_source' => $query
                        ->orderBy([], true)
                        ->groupBy([], true)
                        ->limit(null, 0)
                ])
                ->execute()
                ->first()
                ['count'] ?? 0;
        }

        return $this->count;
    }

    /**
     * Enable or disable auto fields.
     * @param bool Whether to enable or disable auto fields.
     * @return SelectQuery The SelectQuery.
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
        if ($this->result && $this->result instanceof ResultSet) {
            return $this->result->first();
        }

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
     * Get the query result.
     * @return Result The query result.
     */
    public function getResult(): Result
    {
        if ($this->result === null) {
            if (!$this->beforeFindTriggered) {
                $this->model->handleEvent('beforeFind', $this);
                $this->beforeFindTriggered = true;
            }

            $result = $this->execute();

            $this->result = new Result($result, $this, $this->eagerLoad);

            $this->model->handleEvent('afterFind', $this->result);
        }

        return $this->result; 
    }

    /**
     * INNER JOIN a relationship table.
     * @param string $contain The contain string.
     * @param array $conditions The JOIN conditions.
     * @return SelectQuery The SelectQuery.
     */
    public function innerJoinWith(string $contain, array $conditions = []): static
    {
        return $this->containJoin($contain, $conditions, 'INNER');
    }

    /**
     * LEFT JOIN a relationship table.
     * @param string $contain The contain string.
     * @param array $conditions The JOIN conditions.
     * @return SelectQuery The SelectQuery.
     */
    public function leftJoinWith(string $contain, array $conditions = []): static
    {
        return $this->containJoin($contain, $conditions);
    }

    /**
     * INNER JOIN a relationship table and load matching data.
     * @param string $contain The contain string.
     * @param array $conditions The JOIN conditions.
     * @return SelectQuery The SelectQuery.
     */
    public function matching(string $contain, array $conditions = []): static
    {
        return $this->containJoin($contain, $conditions, 'INNER', true);
    }

    /**
     * LEFT JOIN a relationship table and exclude matching rows.
     * @param string $contain The contain string.
     * @param array $conditions The JOIN conditions.
     * @return SelectQuery The SelectQuery.
     */
    public function notMatching(string $contain, array $conditions = []): static
    {
        return $this->containJoin($contain, $conditions, 'LEFT', false);
    }

    /**
     * Prepare the query.
     * @return SelectQuery The SelectQuery.
     */
    public function prepare(): static
    {
        if ($this->prepared) {
            return $this;
        }

        $fields = $this->fields;
        $joins = $this->joins;

        $this->fields = [];
        $this->joins = [];

        $usedAliases = [$this->alias];

        if ($this->autoFields !== false) {
            $this->autoFields($this->model, $this->alias);
        } else if (!$this->subquery) {
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

        $this->originalFields = $fields;
        $this->originalJoins = $joins;
        $this->prepared = true;

        return $this;
    }

    /**
     * Reset the query.
     * @return SelectQuery The SelectQuery.
     */
    public function reset(): static
    {
        if ($this->prepared) {
            $this->fields = $this->originalFields;
            $this->joins = $this->originalJoins;

            $this->originalFields = null;
            $this->originalJoins = null;
            $this->prepared = false;
        }

        return $this;
    }

    /**
     * Set the SELECT fields.
     * @param string|array $fields The fields.
     * @param bool $overwrite Whether to overwrite the existing fields.
     * @return SelectQuery The SelectQuery.
     */
    public function select(string|array $fields = '*', bool $overwrite = false): static
    {
        if ($overwrite) {
            $this->fields = [];
        }

        $this->addFields((array) $fields, $this->model, $this->alias);

        if ($this->fields !== []) {
            $this->autoFields ??= false;
        }

        $this->dirty();

        return $this;
    }

    /**
     * Generate the SQL query.
     * @param ValueBinder|null $binder The ValueBinder.
     * @param bool $reset Whether to reset the prepared query.
     * @return string The SQL query.
     */
    public function sql(ValueBinder|null $binder = null, bool $reset = true): string
    {
        $this->prepare();

        $sql = parent::sql($binder);

        if ($reset) {
            $this->reset();
        }

        return $sql;
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
                if (is_numeric($name)) {
                    $name = $field;
                }

                $this->fields[$name] = $field;
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
     * @return SelectQuery The SelectQuery.
     * @throws OrmException if a relationship is not valid.
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

        $this->dirty();

        return $this;
    }

    /**
     * Mark the query as dirty.
     */
    protected function dirty(): void
    {
        parent::dirty();

        $this->count = null;
        $this->result = null;
    }

}
