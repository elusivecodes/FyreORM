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
use function is_numeric;
use function is_string;
use function str_replace;

/**
 * SelectQuery
 */
class SelectQuery extends \Fyre\DB\Queries\SelectQuery
{
    use ModelTrait;

    public const QUERY_METHODS = [
        'fields' => 'select',
        'contain' => 'contain',
        'join' => 'join',
        'conditions' => 'where',
        'orderBy' => 'orderBy',
        'groupBy' => 'groupBy',
        'having' => 'having',
        'limit' => 'limit',
        'offset' => 'offset',
        'epilog' => 'epilog',
    ];

    protected string $alias;

    protected bool $autoAlias = true;

    protected bool|null $autoFields = null;

    protected bool $beforeFindTriggered = false;

    protected bool $buffering = true;

    protected array $contain = [];

    protected array $containJoin = [];

    protected int|null $count = null;

    protected array $eagerLoadPaths = [];

    protected array $matching = [];

    protected array $options = [];

    protected array|null $originalFields = null;

    protected array|null $originalJoins = null;

    protected bool $prepared = false;

    protected bool|Result|null $result = null;

    /**
     * New SelectQuery constructor.
     *
     * @param Model $model The Model.
     * @param array $options The SelectQuery options.
     */
    public function __construct(Model $model, array $options = [])
    {
        $this->model = $model;

        $options['alias'] ??= $this->model->getAlias();
        $options['autoFields'] ??= null;
        $options['subquery'] ??= false;
        $options['connectionType'] ??= Model::READ;
        $options['events'] ??= true;

        $this->alias = $options['alias'];
        $this->autoAlias = !$options['subquery'];
        $this->autoFields = $options['autoFields'];

        unset($options['alias']);
        unset($options['autoFields']);

        parent::__construct($this->model->getConnection($options['connectionType']), []);

        $this->from([
            $this->alias => $this->model->getTable(),
        ]);

        foreach ($options as $key => $value) {
            if (!array_key_exists($key, static::QUERY_METHODS)) {
                continue;
            }

            $method = static::QUERY_METHODS[$key];
            $this->$method($value);

            unset($options[$key]);
        }

        $this->options = $options;
    }

    /**
     * Get the results.
     *
     * @return Result The results.
     */
    public function all(): Result
    {
        return $this->getResult();
    }

    /**
     * Clear the result.
     *
     * @return SelectQuery The SelectQuery.
     */
    public function clearResult(): static
    {
        $this->result = null;

        return $this;
    }

    /**
     * Set the contain relationships.
     *
     * @param array|string $contain The contain relationships.
     * @param bool $overwrite Whether to overwrite the existing contain.
     * @return SelectQuery The SelectQuery.
     */
    public function contain(array|string $contain, bool $overwrite = false): static
    {
        $contain = Model::normalizeContain($contain, $this->model);

        if ($overwrite) {
            $this->contain = $contain['contain'] ?? [];
        } else {
            $this->contain = Model::mergeContain($this->contain, $contain['contain'] ?? []);
        }

        $this->dirty();

        return $this;
    }

    /**
     * Get the result count.
     *
     * @return int The result count.
     */
    public function count(): int
    {
        if ($this->count === null) {
            $query = clone $this;

            if ($this->options['events'] && !$this->beforeFindTriggered) {
                $this->model->handleEvent('beforeFind', [$query, $this->options]);
            }

            $this->count = $query->getConnection()
                ->select([
                    'count' => 'COUNT(*)',
                ])
                ->from([
                    'count_source' => $query->orderBy([], true),
                ])
                ->execute()
                ->first()['count'] ?? 0;
        }

        return $this->count;
    }

    /**
     * Disable auto fields.
     *
     * @return SelectQuery The SelectQuery.
     */
    public function disableAutoFields(): static
    {
        $this->autoFields = false;

        $this->dirty();

        return $this;
    }

    /**
     * Disable result buffering.
     *
     * @return SelectQuery The SelectQuery.
     */
    public function disableBuffering(): static
    {
        $this->buffering = false;

        $this->dirty();

        return $this;
    }

    /**
     * Enable auto fields.
     *
     * @return SelectQuery The SelectQuery.
     */
    public function enableAutoFields(): static
    {
        $this->autoFields = true;

        $this->dirty();

        return $this;
    }

    /**
     * Enable result buffering.
     *
     * @return SelectQuery The SelectQuery.
     */
    public function enableBuffering(): static
    {
        $this->buffering = true;

        $this->dirty();

        return $this;
    }

    /**
     * Get the first result.
     *
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
     *
     * @return string The alias.
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * Get the connection type.
     *
     * @return string The connection type.
     */
    public function getConnectionType(): string
    {
        return $this->options['connectionType'];
    }

    /**
     * Get the contain array.
     *
     * @return array The contain array.
     */
    public function getContain(): array
    {
        return $this->contain;
    }

    /**
     * Get the eager load paths.
     *
     * @return array The eager load paths.
     */
    public function getEagerLoadPaths(): array
    {
        return $this->eagerLoadPaths;
    }

    /**
     * Get the matching array.
     *
     * @return array The matching array.
     */
    public function getMatching(): array
    {
        return $this->matching;
    }

    /**
     * Get the query options.
     *
     * @return array The query options.
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Get the query result.
     *
     * @return Result The query result.
     */
    public function getResult(): Result
    {
        if ($this->result === null) {
            if ($this->options['events'] && !$this->beforeFindTriggered) {
                $this->model->handleEvent('beforeFind', [$this, $this->options]);
                $this->beforeFindTriggered = true;
            }

            $result = $this->execute();

            $this->result = new Result($result, $this, $this->buffering);

            if ($this->options['events']) {
                $this->model->handleEvent('afterFind', [$this->result, $this->options]);
            }
        }

        return $this->result;
    }

    /**
     * INNER JOIN a relationship table.
     *
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
     *
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
     *
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
     *
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
     *
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

        if ($this->autoFields !== false) {
            $this->autoFields($this->model, $this->alias);
        } else if (!$this->options['subquery']) {
            $this->addFields($this->model->getPrimaryKey(), $this->model, $this->alias);
        }

        $this->fields += $fields;

        foreach ($this->matching as $name => $relationship) {
            $target = $relationship->getTarget();

            if ($this->autoFields !== false) {
                $this->autoFields($target, $name);
            } else {
                $this->addFields($target->getPrimaryKey(), $target, $name);
            }
        }

        $this->containAll($this->contain, $this->model, $this->alias);

        foreach ($this->containJoin as $alias => $join) {
            unset($join['path']);

            $this->joins[$alias] = $join;
        }

        foreach ($joins as $alias => $join) {
            if (is_numeric($alias)) {
                $alias = $join['alias'] ?? $join['table'] ?? null;
            }

            if (!$alias) {
                continue;
            }

            if (array_key_exists($alias, $this->joins)) {
                throw OrmException::forJoinAliasNotUnique($alias);
            }

            unset($join['alias']);

            $this->joins[$alias] = $join;
        }

        $this->originalFields = $fields;
        $this->originalJoins = $joins;
        $this->prepared = true;

        return $this;
    }

    /**
     * Reset the query.
     *
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
     *
     * @param array|string $fields The fields.
     * @param bool $overwrite Whether to overwrite the existing fields.
     * @return SelectQuery The SelectQuery.
     */
    public function select(array|string $fields = '*', bool $overwrite = false): static
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
     *
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
     * Get the results as an array.
     *
     * @return array The results.
     */
    public function toArray(): array
    {
        return $this->getResult()->toArray();
    }

    /**
     * Add SELECT fields.
     *
     * @param array $fields The fields to add.
     * @param Model $model The Model.
     * @param string $alias The table alias.
     * @param bool $overwrite Whether to overwrite existing fields.
     * @param bool $prefixAlias Whether to force prefix the alias.
     */
    protected function addFields(array $fields, Model $model, string $alias, bool $overwrite = true, bool $prefixAlias = false): void
    {
        foreach ($fields as $name => $field) {
            if ($field === '*') {
                $this->autoFields($model, $alias);

                continue;
            }

            if (is_string($field)) {
                $field = $model->aliasField($field, $alias);
            }

            if (!$this->autoAlias && is_numeric($name)) {
                $this->fields[] = $field;

                continue;
            }

            if (is_numeric($name)) {
                $name = str_replace('.', '__', $field);
            } else if ($prefixAlias) {
                $name = $alias.'__'.$name;
            }

            if (!$overwrite && array_key_exists($name, $this->fields)) {
                continue;
            }

            $this->fields[$name] = $field;
        }
    }

    /**
     * Automatically add SELECT fields from a Model schema.
     *
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
     *
     * @param array $contain The contain relationships.
     * @param Model $model The Model.
     * @param string $alias The table alias.
     * @param string $pathPrefix The path prefix.
     */
    protected function containAll(array $contain, Model $model, string $alias, string $pathPrefix = ''): void
    {
        foreach ($contain as $name => $data) {
            $relationship = $model->getRelationship($name);

            $data['strategy'] ??= $relationship->getStrategy();

            if ($data['strategy'] !== 'join') {
                $bindingKey = $relationship->getBindingKey();
                $this->addFields([$bindingKey], $model, $alias);
                $this->eagerLoadPaths[] = $pathPrefix.'.'.$name;

                continue;
            }

            if (array_key_exists('callback', $data) && $data['callback']) {
                throw OrmException::forInvalidStrategyContainCallback($data['strategy']);
            }

            $target = $relationship->getTarget();

            $data['autoFields'] ??= $this->autoFields;

            $joins = $relationship->buildJoins([
                'alias' => $name,
                'sourceAlias' => $alias,
                'type' => $data['type'] ?? null,
                'conditions' => $data['conditions'] ?? [],
            ]);

            $path = $pathPrefix;
            $usedAlias = false;
            foreach ($joins as $joinAlias => $join) {
                $path .= '.'.$joinAlias;

                if (array_key_exists($joinAlias, $this->joins)) {
                    $usedAlias = true;
                    break;
                }

                if (array_key_exists($joinAlias, $this->containJoin) && $path !== $this->containJoin[$joinAlias]['path']) {
                    $usedAlias = true;
                    break;
                }
            }

            if ($usedAlias) {
                $bindingKey = $relationship->getBindingKey();
                $this->addFields([$bindingKey], $model, $alias);
                $this->eagerLoadPaths[] = $pathPrefix.'.'.$name;

                continue;
            }

            foreach ($joins as $joinAlias => $join) {
                $this->joins[$joinAlias] ??= $join;
            }

            if (array_key_exists('fields', $data)) {
                $this->addFields($data['fields'], $target, $name, prefixAlias: true);
            }

            if ($data['autoFields'] !== false) {
                $this->autoFields($target, $name);
            } else {
                $this->addFields($target->getPrimaryKey(), $target, $name);
            }

            $this->containAll($data['contain'], $target, $name, $path);
        }
    }

    /**
     * Add a relationship JOIN.
     *
     * @param string $contain The contain string.
     * @param array $conditions The JOIN conditions.
     * @param string $type The JOIN type.
     * @param bool|null $matching Whether this is a matching/noMatching join.
     * @return SelectQuery The SelectQuery.
     *
     * @throws OrmException if a relationship is not valid.
     */
    protected function containJoin(string $contain, array $conditions, string $type = 'LEFT', bool|null $matching = null): static
    {
        $contain = explode('.', $contain);
        $lastContain = count($contain) - 1;

        $model = $this->model;
        $sourceAlias = $this->alias;

        $path = '';
        foreach ($contain as $i => $alias) {
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
                'type' => $type,
            ]);

            foreach ($joins as $joinAlias => $join) {
                $path .= '.'.$joinAlias;

                if (array_key_exists($joinAlias, $this->containJoin) && $path !== $this->containJoin[$joinAlias]['path']) {
                    throw OrmException::forJoinAliasNotUnique($joinAlias);
                }

                $join['path'] = $path;

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
