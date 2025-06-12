<?php
declare(strict_types=1);

namespace Fyre\ORM;

use ArrayObject;
use BadMethodCallException;
use Fyre\Container\Container;
use Fyre\DB\Connection;
use Fyre\DB\ConnectionManager;
use Fyre\DB\QueryGenerator;
use Fyre\Entity\Entity;
use Fyre\Entity\EntityLocator;
use Fyre\Event\EventDispatcherTrait;
use Fyre\Event\EventListenerInterface;
use Fyre\Event\EventManager;
use Fyre\ORM\Exceptions\OrmException;
use Fyre\ORM\Queries\DeleteQuery;
use Fyre\ORM\Queries\InsertQuery;
use Fyre\ORM\Queries\ReplaceQuery;
use Fyre\ORM\Queries\SelectQuery;
use Fyre\ORM\Queries\UpdateBatchQuery;
use Fyre\ORM\Queries\UpdateQuery;
use Fyre\ORM\Relationships\BelongsTo;
use Fyre\ORM\Relationships\HasMany;
use Fyre\ORM\Relationships\HasOne;
use Fyre\ORM\Relationships\ManyToMany;
use Fyre\ORM\Relationships\Relationship;
use Fyre\Schema\SchemaRegistry;
use Fyre\Schema\TableSchema;
use Fyre\Utility\Inflector;
use Fyre\Validation\Validator;
use InvalidArgumentException;
use ReflectionClass;
use Traversable;

use function array_diff_assoc;
use function array_filter;
use function array_intersect;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_reduce;
use function array_reverse;
use function array_shift;
use function array_values;
use function count;
use function explode;
use function in_array;
use function is_array;
use function is_numeric;
use function is_string;
use function iterator_to_array;
use function method_exists;
use function preg_replace;

use const ARRAY_FILTER_USE_KEY;

/**
 * Model
 */
class Model implements EventListenerInterface
{
    use EventDispatcherTrait;

    public const READ = 'read';

    public const WRITE = 'write';

    protected string $alias;

    protected string|null $autoIncrementKey = null;

    protected array $behaviors = [];

    protected string $classAlias;

    protected array $connectionKeys = [
        self::WRITE => 'default',
    ];

    protected array $connections = [];

    protected string|null $displayName = null;

    protected array $primaryKey;

    protected array $relationships = [];

    protected string|null $routeKey = null;

    protected RuleSet $rules;

    protected string $table;

    protected Validator $validator;

    /**
     * Recursively merge contain data.
     *
     * @param array $contain The original contain.
     * @param array $newContain The new contain.
     * @return array The merged contain data.
     */
    public static function mergeContain(array $contain, array $newContain): array
    {
        foreach ($newContain as $name => $data) {
            if (!array_key_exists($name, $contain)) {
                $contain[$name] = $data;

                continue;
            }

            foreach ($data as $key => $value) {
                if ($key === 'contain') {
                    $contain[$name][$key] = static::mergeContain($contain[$name][$key], $value);
                } else if ($key === 'callback') {
                    $oldValue = $contain[$name][$key] ?? null;
                    if ($oldValue === null) {
                        $contain[$name][$key] = $value;
                    } else if ($value !== null) {
                        $contain[$name][$key] = fn(SelectQuery $query): SelectQuery => $value($oldValue($query));
                    }
                } else {
                    $contain[$name][$key] = $value;
                }
            }
        }

        return $contain;
    }

    /**
     * Normalize contain data.
     *
     * @param array|string $contain The contain data.
     * @param Model $model The Model.
     * @param string $containKey The key for the contains.
     * @param int $depth The contain depth.
     * @return array The normalized contain data.
     *
     * @throws OrmException if a relationship is not valid.
     */
    public static function normalizeContain(array|string $contain, Model $model, string $containKey = 'contain', int $depth = 0): array
    {
        $normalized = [
            $containKey => [],
        ];

        if ($contain === '' || $contain === []) {
            return $normalized;
        }

        if (is_string($contain)) {
            $contain = explode('.', $contain);

            $contain = array_reduce(
                array_reverse($contain),
                fn(array $acc, string $value): array => $value ?
                    [
                        $value => [
                            $acc,
                        ],
                    ] :
                    $acc,
                []
            );
        }

        foreach ($contain as $key => $value) {
            if (is_numeric($key) || $key === $containKey) {
                $newContain = static::normalizeContain($value, $model, $containKey, $depth);
                $normalized = static::mergeContain($normalized, $newContain);

                continue;
            }

            $relationship = $model->getRelationship($key);

            if ($relationship) {
                $normalized[$containKey][$key] ??= [];
                $newContain = static::normalizeContain($value, $relationship->getTarget(), $containKey, $depth + 1);
                $normalized[$containKey][$key] = static::mergeContain($normalized[$containKey][$key], $newContain);

                continue;
            }

            $validKeys = [];

            if ($depth > 0) {
                if ($containKey === 'associated') {
                    $validKeys = ['accessible', 'clean', 'events', 'guard', 'mutate', 'new', 'parse', 'validate'];
                } else {
                    $validKeys = ['autoFields', 'callback', 'strategy', 'type'];
                    $validKeys = array_merge($validKeys, SelectQuery::QUERY_METHODS);
                }
            }

            if (in_array($key, $validKeys)) {
                $normalized[$key] = $key === 'callback' && $value ?
                    fn(SelectQuery $query): SelectQuery => $value($query) :
                    $value;

                continue;
            }

            throw OrmException::forInvalidRelationship($key);
        }

        return $normalized;
    }

    /**
     * New Model constructor.
     *
     * @param Container $container The Container.
     * @param ConnectionManager $connectionManager The Connection Manager.
     * @param SchemaRegistry $schemaRegistry The SchemaRegistry.
     * @param BehaviorRegistry $behaviorRegistry The BehaviorRegistry.
     * @param EntityLocator $entityLocator The EntityLocator.
     * @param Inflector $inflector The Inflector.
     * @param EventManager $eventManager The EventManager.
     */
    public function __construct(
        protected Container $container,
        protected ConnectionManager $connectionManager,
        protected SchemaRegistry $schemaRegistry,
        protected BehaviorRegistry $behaviorRegistry,
        protected EntityLocator $entityLocator,
        protected Inflector $inflector,
        EventManager $eventManager
    ) {
        $this->eventManager = new EventManager($eventManager);

        $this->eventManager->addListener($this);

        if (method_exists($this, 'initialize')) {
            $this->initialize();
        }
    }

    /**
     * Call a method on the behaviors.
     *
     * @param string $name The method name.
     * @param array $arguments The method arguments.
     * @return mixed The result.
     */
    public function __call(string $name, array $arguments): mixed
    {
        foreach ($this->behaviors as $behavior) {
            if (method_exists($behavior, $name)) {
                return $behavior->$name(...$arguments);
            }
        }

        throw new BadMethodCallException('Invalid method: '.$name);
    }

    /**
     * Get a Relationship.
     *
     * @param string $name The name.
     * @return Relationship The Relationship.
     */
    public function __get(string $name): Relationship
    {
        if (array_key_exists($name, $this->relationships)) {
            return $this->relationships[$name];
        }

        throw new InvalidArgumentException('Invalid relationship: '.$name);
    }

    /**
     * Add a Behavior to the Model.
     *
     * @param string $name The behavior name.
     * @param array $options The behavior options.
     * @return Model The Model.
     *
     * @throws OrmException if the behavior exists.
     */
    public function addBehavior(string $name, array $options = []): static
    {
        if ($this->hasBehavior($name)) {
            throw OrmException::forBehaviorExists($name);
        }

        $this->behaviors[$name] = $this->behaviorRegistry->build($name, $this, $options);

        $this->eventManager->addListener($this->behaviors[$name]);

        return $this;
    }

    /**
     * Add a Relationship.
     *
     * @param Relationship $relationship The Relationship.
     * @return Model The Model.
     *
     * @throws OrmException if relationship alias or property is already used.
     */
    public function addRelationship(Relationship $relationship): static
    {
        $name = $relationship->getName();

        if (array_key_exists($name, $this->relationships)) {
            throw OrmException::forRelationshipNotUnique($name);
        }

        $property = $relationship->getProperty();

        if ($this->getSchema()->hasColumn($property)) {
            throw OrmException::forRelationshipColumnName($property);
        }

        $this->relationships[$name] = $relationship;

        return $this;
    }

    /**
     * Alias a field name.
     *
     * @param string $field The field name.
     * @param string|null $alias The alias.
     * @return string The aliased field.
     */
    public function aliasField(string $field, string|null $alias = null): string
    {
        if (!$this->getSchema()->hasColumn($field)) {
            return $field;
        }

        $alias ??= $this->getAlias();

        return $alias.'.'.$field;
    }

    /**
     * Create a "belongs to" relationship.
     *
     * @param string $name The relationship name.
     * @param array $data The relationship data.
     * @return BelongsTo The relationship.
     */
    public function belongsTo(string $name, array $data = []): BelongsTo
    {
        $data['source'] = $this;

        $relationship = $this->container->build(BelongsTo::class, ['name' => $name, 'options' => $data]);

        $this->addRelationship($relationship);

        return $relationship;
    }

    /**
     * Build the model RuleSet.
     *
     * @param RuleSet $rules The RuleSet.
     * @return RuleSet The RuleSet.
     */
    public function buildRules(RuleSet $rules): RuleSet
    {
        return $rules;
    }

    /**
     * Build the model Validator.
     *
     * @param Validator $validator The Validator.
     * @return Validator The Validator.
     */
    public function buildValidation(Validator $validator): Validator
    {
        return $validator;
    }

    /**
     * Delete an Entity.
     *
     * @param Entity $entity The Entity.
     * @param array $options The options for deleting.
     * @return bool TRUE if the delete was successful, otherwise FALSE.
     */
    public function delete(Entity $entity, array $options = []): bool
    {
        $options['events'] ??= true;
        $options['cascade'] ??= true;

        $connection = $this->getConnection();

        $connection->begin();

        if (!$this->_delete($entity, $options)) {
            $connection->rollback();

            static::resetParents([$entity], $this);
            static::resetChildren([$entity], $this);

            $entity->clearTemporaryFields();

            return false;
        }

        if ($options['events']) {
            $connection->afterCommit(function() use ($entity, $options): void {
                $this->dispatchEvent('Orm.afterDeleteCommit', ['entity' => $entity, 'options' => $options], false);
            }, 100);
        }

        $connection->afterCommit(function() use ($entity): void {
            static::cleanEntities([$entity], $this);
        }, 200);

        $connection->commit();

        return true;
    }

    /**
     * Delete all rows matching conditions.
     *
     * @param array $conditions The conditions.
     * @return int The number of rows affected.
     */
    public function deleteAll(array $conditions): int
    {
        $this->deleteQuery()
            ->where($conditions)
            ->execute();

        return $this->getConnection()->affectedRows();
    }

    /**
     * Delete multiple entities.
     *
     * @param array|Traversable $entities The entities.
     * @param array $options The options for deleting.
     * @return bool TRUE if the delete was successful, otherwise FALSE.
     */
    public function deleteMany(array|Traversable $entities, array $options = []): bool
    {
        if (!is_array($entities)) {
            $entities = iterator_to_array($entities);
        }

        if ($entities === []) {
            return true;
        }

        static::checkEntities($entities);

        if (count($entities) === 1) {
            return $this->delete($entities[0], $options);
        }

        $options['events'] ??= true;
        $options['cascade'] ??= true;

        $connection = $this->getConnection();

        $connection->begin();

        $result = true;
        foreach ($entities as $entity) {
            if (!$this->_delete($entity, $options)) {
                $result = false;
                break;
            }
        }

        if (!$result) {
            $connection->rollback();

            static::resetParents($entities, $this);
            static::resetChildren($entities, $this);

            foreach ($entities as $entity) {
                $entity->clearTemporaryFields();
            }

            return false;
        }

        if ($options['events']) {
            $connection->afterCommit(function() use ($entities, $options): void {
                foreach ($entities as $entity) {
                    $this->dispatchEvent('Orm.afterDeleteCommit', ['entity' => $entity, 'options' => $options], false);
                }
            }, 100);
        }

        $connection->afterCommit(function() use ($entities): void {
            static::cleanEntities($entities, $this);
        }, 200);

        $connection->commit();

        return true;
    }

    /**
     * Create a new DeleteQuery.
     *
     * @param array $options The option for the query.
     * @return DeleteQuery The DeleteQuery.
     */
    public function deleteQuery(array $options = []): DeleteQuery
    {
        return new DeleteQuery($this, $options);
    }

    /**
     * Determine whether matching rows exist.
     *
     * @param array $conditions The conditions.
     * @return bool TRUE if matching rows exist, otherwise FALSE.
     */
    public function exists(array $conditions): bool
    {
        return $this->find()
            ->disableAutoFields()
            ->where($conditions)
            ->limit(1)
            ->count() > 0;
    }

    /**
     * Create a new SelectQuery.
     *
     * @param array $options The find options.
     * @return SelectQuery The Query.
     */
    public function find(array $options = []): SelectQuery
    {
        $options['alias'] ??= null;
        $options['connectionType'] ??= static::READ;

        return $this->selectQuery($options);
    }

    /**
     * Retrieve a single entity.
     *
     * @param array|int|string $primaryValues The primary key values.
     * @param array $data The find data.
     * @return Entity|null The Entity.
     */
    public function get(array|int|string $primaryValues, array $data = []): Entity|null
    {
        $primaryKeys = $this->getPrimaryKey();
        $primaryKeys = array_map(
            fn(string $key): string => $this->aliasField($key),
            $primaryKeys
        );
        $conditions = QueryGenerator::combineConditions($primaryKeys, (array) $primaryValues);

        return $this->find($data)
            ->where($conditions)
            ->first();
    }

    /**
     * Get the model alias.
     *
     * @return string The model alias.
     */
    public function getAlias(): string
    {
        return $this->alias ??= $this->getClassAlias();
    }

    /**
     * Get the table auto increment column.
     *
     * @return string|null The table auto increment column.
     */
    public function getAutoIncrementKey(): string|null
    {
        if (!$this->autoIncrementKey) {
            $schema = $this->getSchema();

            foreach ($this->getPrimaryKey() as $key) {
                $column = $schema->column($key);

                if (!array_key_exists('autoIncrement', $column) || !$column['autoIncrement']) {
                    continue;
                }

                $this->autoIncrementKey = $key;
                break;
            }
        }

        return $this->autoIncrementKey;
    }

    /**
     * Get a loaded Behavior.
     *
     * @param string $name The behavior name.
     * @return Behavior|null The Behavior.
     */
    public function getBehavior(string $name): Behavior|null
    {
        return $this->behaviors[$name] ?? null;
    }

    /**
     * Get the model class alias.
     *
     * @return string The model class alias.
     */
    public function getClassAlias(): string
    {
        return $this->classAlias ??= preg_replace('/Model$/', '', (new ReflectionClass($this))->getShortName());
    }

    /**
     * Get the Connection.
     *
     * @param string|null $type The connection type.
     * @return Connection The Connection.
     */
    public function getConnection(string|null $type = null): Connection
    {
        if (!array_key_exists($type, $this->connections) && !array_key_exists($type, $this->connectionKeys)) {
            $type = static::WRITE;
        }

        return $this->connections[$type] ??= $this->connectionManager->use($this->connectionKeys[$type] ?? $this->connectionKeys[static::WRITE]);
    }

    /**
     * Get the display name.
     *
     * @return string The display name.
     */
    public function getDisplayName(): string
    {
        if (!$this->displayName) {
            $testColumns = array_merge(['name', 'title', 'label'], $this->getPrimaryKey());
            $columns = $this->getSchema()->columnNames();
            $matching = array_intersect($testColumns, $columns);

            $this->displayName = array_shift($matching);
        }

        return $this->displayName;
    }

    /**
     * Get the primary key(s).
     *
     * @return array The primary key(s).
     */
    public function getPrimaryKey(): array
    {
        return $this->primaryKey ??= $this->getSchema()->primaryKey();
    }

    /**
     * Get a Relationship.
     *
     * @param string $name The relationship name.
     * @return Relationship|null The Relationship.
     */
    public function getRelationship(string $name): Relationship|null
    {
        return $this->relationships[$name] ?? null;
    }

    /**
     * Get all relationships.
     *
     * @return array The relationships.
     */
    public function getRelationships(): array
    {
        return $this->relationships;
    }

    /**
     * Get the display name.
     *
     * @return string The display name.
     */
    public function getRouteKey(): string
    {
        if (!$this->routeKey) {
            $testColumns = array_merge(['slug'], $this->getPrimaryKey());
            $columns = $this->getSchema()->columnNames();
            $matching = array_intersect($testColumns, $columns);

            $this->routeKey = array_shift($matching);
        }

        return $this->routeKey;
    }

    /**
     * Get the model RuleSet.
     *
     * @return RuleSet The RuleSet.
     */
    public function getRules(): RuleSet
    {
        return $this->rules ??= $this->buildRules($this->container->build(RuleSet::class, ['model' => $this]));
    }

    /**
     * Get the TableSchema.
     *
     * @param string|null $type The connection type.
     * @return TableSchema The TableSchema.
     */
    public function getSchema(string|null $type = null): TableSchema
    {
        return $this->schemaRegistry->use($this->getConnection($type))
            ->describe($this->getTable());
    }

    /**
     * Get the table name.
     *
     * @return string The table name.
     */
    public function getTable(): string
    {
        return $this->table ??= $this->inflector->underscore($this->getClassAlias());
    }

    /**
     * Get the model Validator.
     *
     * @return Validator The Validator.
     */
    public function getValidator(): Validator
    {
        return $this->validator ??= $this->buildValidation($this->container->build(Validator::class));
    }

    /**
     * Determine whether the Model has a Behavior.
     *
     * @param string $name The behavior name.
     * @return bool TRUE if the Model has the Behavior, otherwise FALSE.
     */
    public function hasBehavior(string $name): bool
    {
        return array_key_exists($name, $this->behaviors);
    }

    /**
     * Create a "has many" relationship.
     *
     * @param string $name The relationship name.
     * @param array $data The relationship data.
     * @return HasMany The relationship.
     */
    public function hasMany(string $name, array $data = []): HasMany
    {
        $data['source'] = $this;

        $relationship = $this->container->build(HasMany::class, ['name' => $name, 'options' => $data]);

        $this->addRelationship($relationship);

        return $relationship;
    }

    /**
     * Create a "has one" relationship.
     *
     * @param string $name The relationship name.
     * @param array $data The relationship data.
     * @return HasOne The relationship.
     */
    public function hasOne(string $name, array $data = []): HasOne
    {
        $data['source'] = $this;

        $relationship = $this->container->build(HasOne::class, ['name' => $name, 'options' => $data]);

        $this->addRelationship($relationship);

        return $relationship;
    }

    /**
     * Determine whether a Relationship exists.
     *
     * @param string $name The relationship name.
     * @return bool TRUE if the Relationship exists, otherwise FALSE.
     */
    public function hasRelationship(string $name): bool
    {
        return array_key_exists($name, $this->relationships);
    }

    /**
     * Get the implemented events.
     *
     * @return array The implemented events.
     */
    public function implementedEvents(): array
    {
        return array_filter([
            'Orm.afterDelete' => 'afterDelete',
            'Orm.afterDeleteCommit' => 'afterDeleteCommit',
            'Orm.afterFind' => 'afterFind',
            'Orm.afterParse' => 'afterParse',
            'Orm.afterRules' => 'afterRules',
            'Orm.afterSave' => 'afterSave',
            'Orm.afterSaveCommit' => 'afterSaveCommit',
            'Orm.beforeDelete' => 'beforeDelete',
            'Orm.beforeFind' => 'beforeFind',
            'Orm.beforeParse' => 'beforeParse',
            'Orm.beforeRules' => 'beforeRules',
            'Orm.beforeSave' => 'beforeSave',
        ], fn(string $method): bool => method_exists($this, $method));
    }

    /**
     * Create a new InsertQuery.
     *
     * @return InsertQuery The InsertQuery.
     */
    public function insertQuery(): InsertQuery
    {
        return new InsertQuery($this);
    }

    /**
     * Load contained data into entity.
     *
     * @param Entity $entity The entity.
     * @param array $contain The relationships to contain.
     * @return Entity|null The entity.
     */
    public function loadInto(Entity $entity, array $contain): Entity|null
    {
        $primaryKeys = $this->getPrimaryKey();
        $primaryValues = $entity->extract($primaryKeys);

        $tempEntity = $this->get($primaryValues, [
            'contain' => $contain,
            'autoFields' => false,
        ]);

        if (!$tempEntity) {
            return $entity;
        }

        foreach ($this->relationships as $relationship) {
            $property = $relationship->getProperty();

            if (!$tempEntity->has($property)) {
                continue;
            }

            $value = $tempEntity->get($property);
            $entity->set($property, $value);
            $entity->setDirty($property, false);
        }

        return $entity;
    }

    /**
     * Create a "many to many" relationship.
     *
     * @param string $name The relationship name.
     * @param array $data The relationship data.
     * @return ManyToMany The relationship.
     */
    public function manyToMany(string $name, array $data = []): ManyToMany
    {
        $data['source'] = $this;

        $relationship = $this->container->build(ManyToMany::class, ['name' => $name, 'options' => $data]);

        $this->addRelationship($relationship);

        return $relationship;
    }

    /**
     * Build a new empty Entity.
     *
     * @return Entity The Entity.
     */
    public function newEmptyEntity(): Entity
    {
        return $this->createEntity();
    }

    /**
     * Build multiple new entities using data.
     *
     * @param array $data The data.
     * @param array $options The Entity options.
     * @return array The entities.
     */
    public function newEntities(array $data, array $options = []): array
    {
        return array_map(
            fn(array $values): Entity => $this->newEntity($values, $data, $options),
            $data
        );
    }

    /**
     * Build a new Entity using data.
     *
     * @param array $data The data.
     * @param array $options The Entity options.
     * @return Entity The Entity.
     */
    public function newEntity(array $data, array $options = []): Entity
    {
        $entity = $this->createEntity();

        $this->injectInto($entity, $data, $options);

        return $entity;
    }

    /**
     * Parse data from user.
     *
     * @param array $data The data.
     * @return array The user values.
     */
    public function parseSchema(array $data): array
    {
        $schema = $this->getSchema();

        foreach ($data as $field => $value) {
            if (!$schema->hasColumn($field)) {
                continue;
            }

            $data[$field] = $schema
                ->getType($field)
                ->parse($value);
        }

        return $data;
    }

    /**
     * Update multiple entities using data.
     *
     * @param array|Traversable $entities The entities.
     * @param array $data The data.
     * @param array $options The Entity options.
     */
    public function patchEntities(array|Traversable $entities, array $data, array $options = []): void
    {
        foreach ($entities as $i => $entity) {
            if (!array_key_exists($i, $data)) {
                continue;
            }

            $this->patchEntity($entity, $data[$i], $options);
        }
    }

    /**
     * Update an Entity using data.
     *
     * @param Entity $entity The Entity.
     * @param array $data The data.
     * @param array $options The Entity options.
     */
    public function patchEntity(Entity $entity, array $data, array $options = []): void
    {
        $this->injectInto($entity, $data, $options);
    }

    /**
     * Remove a Behavior from the Model.
     *
     * @param string $name The behavior name.
     * @return Model The Model.
     *
     * @throws OrmException if the behavior does not exist.
     */
    public function removeBehavior(string $name): static
    {
        if (!$this->hasBehavior($name)) {
            throw OrmException::forMissingBehavior($name);
        }

        $this->eventManager->removeListener($this->behaviors[$name]);

        unset($this->behaviors[$name]);

        return $this;
    }

    /**
     * Remove an existing Relationship.
     *
     * @param string $name The relationship name.
     * @return Model The Model.
     */
    public function removeRelationship(string $name): static
    {
        unset($this->relationships[$name]);

        return $this;
    }

    /**
     * Create a new ReplaceQuery.
     *
     * @return ReplaceQuery The ReplaceQuery.
     */
    public function replaceQuery(): ReplaceQuery
    {
        return new ReplaceQuery($this);
    }

    /**
     * Resolve an entity from a route.
     *
     * @param int|string $value The value.
     * @param string $field The field.
     * @param Entity|null $parent The parent Entity.
     * @return Entity|null The Entity.
     */
    public function resolveRouteBinding(int|string $value, string $field, Entity|null $parent = null): Entity|null
    {
        $query = $this->find()
            ->where([
                $this->aliasField($field) => $value,
            ]);

        if ($parent) {
            $source = $parent->getSource();
            $relationship = $this->getRelationship($source);

            if ($relationship) {
                $Target = $relationship->getTarget();

                $primaryKeys = $Target->getPrimaryKey();

                $targetFields = array_map(
                    fn(string $targetField): string => $Target->aliasField($targetField),
                    $primaryKeys
                );

                $primaryValues = $parent->extract($primaryKeys);
                $conditions = QueryGenerator::combineConditions($targetFields, $primaryValues);

                $query->innerJoinWith($source, $conditions);
            }
        }

        return $query->first();
    }

    /**
     * Save an Entity.
     *
     * @param Entity $entity The Entity.
     * @param array $options The options for saving.
     * @return bool TRUE if the save was successful, otherwise FALSE.
     */
    public function save(Entity $entity, array $options = []): bool
    {
        if (!$entity->isNew() && !$entity->isDirty()) {
            return true;
        }

        if ($entity->hasErrors()) {
            return false;
        }

        $options['checkExists'] ??= true;
        $options['checkRules'] ??= true;
        $options['saveRelated'] ??= true;
        $options['events'] ??= true;
        $options['clean'] ??= true;

        if ($options['checkExists']) {
            $this->checkExists([$entity]);
        }

        $connection = $this->getConnection();

        $connection->begin();

        if (!$this->_save($entity, $options)) {
            $connection->rollback();

            static::resetParents([$entity], $this);
            static::resetChildren([$entity], $this);

            $entity->clearTemporaryFields();

            return false;
        }

        if ($options['events']) {
            $connection->afterCommit(function() use ($entity, $options): void {
                $this->dispatchEvent('Orm.afterSaveCommit', ['entity' => $entity, 'options' => $options], false);
            }, 100);
        }

        if ($options['clean']) {
            $connection->afterCommit(function() use ($entity): void {
                static::cleanEntities([$entity], $this);
            }, 200);
        }

        $connection->commit();

        return true;
    }

    /**
     * Save multiple entities.
     *
     * @param array|Traversable $entities The entities.
     * @param array $options The options for saving.
     * @return bool TRUE if the save was successful, otherwise FALSE.
     */
    public function saveMany(array|Traversable $entities, array $options = []): bool
    {
        if (!is_array($entities)) {
            $entities = iterator_to_array($entities);
        }

        $entities = array_filter(
            $entities,
            fn(Entity $entity): bool => $entity->isNew() || $entity->isDirty()
        );

        if ($entities === []) {
            return true;
        }

        static::checkEntities($entities);

        if (count($entities) === 1) {
            return $this->save($entities[0], $options);
        }

        foreach ($entities as $entity) {
            if ($entity->hasErrors()) {
                return false;
            }
        }

        $options['checkExists'] ??= true;
        $options['checkRules'] ??= true;
        $options['saveRelated'] ??= true;
        $options['events'] ??= true;
        $options['clean'] ??= true;

        if ($options['checkExists']) {
            $this->checkExists($entities);
        }

        $connection = $this->getConnection();

        $connection->begin();

        $result = true;
        foreach ($entities as $entity) {
            if (!$this->_save($entity, $options)) {
                $result = false;
                break;
            }
        }

        if (!$result) {
            $connection->rollback();

            static::resetParents($entities, $this);
            static::resetChildren($entities, $this);

            foreach ($entities as $entity) {
                $entity->clearTemporaryFields();
            }

            return false;
        }

        if ($options['events']) {
            $connection->afterCommit(function() use ($entities, $options): void {
                foreach ($entities as $entity) {
                    $this->dispatchEvent('Orm.afterSaveCommit', ['entity' => $entity, 'options' => $options], false);
                }
            }, 100);
        }

        if ($options['clean']) {
            $connection->afterCommit(function() use ($entities): void {
                static::cleanEntities($entities, $this);
            }, 200);
        }

        $connection->commit();

        return true;
    }

    /**
     * Create a new SelectQuery.
     *
     * @param array $options The option for the query.
     * @return SelectQuery The SelectQuery.
     */
    public function selectQuery(array $options = []): SelectQuery
    {
        return new SelectQuery($this, $options);
    }

    /**
     * Set the model alias.
     *
     * @param string $alias The model alias.
     * @return Model The Model.
     */
    public function setAlias(string $alias): static
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * Set the model class alias.
     *
     * @param string $classAlias The model class alias.
     * @return Model The Model.
     */
    public function setClassAlias(string $classAlias): static
    {
        $this->classAlias = $classAlias;

        return $this;
    }

    /**
     * Set the Connection.
     *
     * @param Connection $connection The Connection.
     * @param string $type The connection type.
     * @return Model The Model.
     */
    public function setConnection(Connection $connection, string $type = self::WRITE): static
    {
        $this->connections[$type] = $connection;

        return $this;
    }

    /**
     * Set the display name.
     *
     * @param string $displayName The display name.
     * @return Model The Model.
     */
    public function setDisplayName(string $displayName): static
    {
        $this->displayName = $displayName;

        return $this;
    }

    /**
     * Set the model RuleSet.
     *
     * @param RuleSet $rules The RuleSet.
     * @return Model The Model.
     */
    public function setRules(RuleSet $rules): static
    {
        $this->rules = $rules;

        return $this;
    }

    /**
     * Set the table name.
     *
     * @param string $table The table name.
     * @return Model The Model.
     */
    public function setTable(string $table): static
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Set the model Validator.
     *
     * @param Validator $validator The Validator.
     * @return Model The Model.
     */
    public function setValidator(Validator $validator): static
    {
        $this->validator = $validator;

        return $this;
    }

    /**
     * Create a new subquery SelectQuery.
     *
     * @param array $options The option for the query.
     * @return SelectQuery The SelectQuery.
     */
    public function subquery(array $options = []): SelectQuery
    {
        return $this->selectQuery($options + ['subquery' => true]);
    }

    /**
     * Convert data to database.
     *
     * @param array $data The data.
     * @return array The database values.
     */
    public function toDatabaseSchema(array $data): array
    {
        $schema = $this->getSchema();

        foreach ($data as $field => $value) {
            $data[$field] = $schema
                ->getType($field)
                ->toDatabase($value);
        }

        return $data;
    }

    /**
     * Update all rows matching conditions.
     *
     * @param array $data The data to update.
     * @param array $conditions The conditions.
     * @return int The number of rows affected.
     */
    public function updateAll(array $data, array $conditions): int
    {
        $this->updateQuery()
            ->set($data)
            ->where($conditions)
            ->execute();

        return $this->getConnection()->affectedRows();
    }

    /**
     * Create a new UpdateBatchQuery.
     *
     * @param array $options The option for the query.
     * @return UpdateBatchQuery The UpdateBatchQuery.
     */
    public function updateBatchQuery(array $options = []): UpdateBatchQuery
    {
        return new UpdateBatchQuery($this, $options);
    }

    /**
     * Create a new UpdateQuery.
     *
     * @param array $options The option for the query.
     * @return UpdateQuery The UpdateQuery.
     */
    public function updateQuery(array $options = []): UpdateQuery
    {
        return new UpdateQuery($this, $options);
    }

    /**
     * Delete a single Entity.
     *
     * @param Entity $entity The Entity.
     * @param array $options The options for saving.
     * @return bool TRUE if the save was successful, otherwise FALSE.
     */
    protected function _delete(Entity $entity, array $options): bool
    {
        if ($options['events']) {
            $event = $this->dispatchEvent('Orm.beforeDelete', ['entity' => $entity, 'options' => $options]);

            if ($event->isStopped()) {
                return (bool) $event->getResult();
            }
        }

        $primaryKeys = $this->getPrimaryKey();
        $primaryValues = $entity->extract($primaryKeys);
        $conditions = QueryGenerator::combineConditions($primaryKeys, $primaryValues);

        if (!$this->deleteAll($conditions)) {
            return false;
        }

        if ($options['cascade'] && !$this->deleteChildren([$entity], $options)) {
            return false;
        }

        if ($options['events']) {
            $event = $this->dispatchEvent('Orm.afterDelete', ['entity' => $entity, 'options' => $options]);

            if ($event->isStopped()) {
                return (bool) $event->getResult();
            }
        }

        return true;
    }

    /**
     * Save a single Entity.
     *
     * @param Entity $entity The Entity.
     * @param array $options The options for saving.
     * @return bool TRUE if the save was successful, otherwise FALSE.
     */
    protected function _save(Entity $entity, array $options): bool
    {
        if ($options['checkRules']) {
            if ($options['events']) {
                $event = $this->dispatchEvent('Orm.beforeRules', ['entity' => $entity, 'options' => $options]);

                if ($event->isStopped()) {
                    return (bool) $event->getResult();
                }
            }

            if (!$this->getRules()->validate($entity)) {
                return false;
            }

            if ($options['events']) {
                $event = $this->dispatchEvent('Orm.afterRules', ['entity' => $entity, 'options' => $options]);

                if ($event->isStopped()) {
                    return (bool) $event->getResult();
                }
            }
        }

        if ($options['events']) {
            $event = $this->dispatchEvent('Orm.beforeSave', ['entity' => $entity, 'options' => $options]);

            if ($event->isStopped()) {
                return (bool) $event->getResult();
            }
        }

        if ($options['saveRelated'] && !$this->saveParents($entity, $options)) {
            return false;
        }

        $schema = $this->getSchema();
        $columns = $schema->columnNames();
        $primaryKeys = $this->getPrimaryKey();
        $autoIncrementKey = $this->getAutoIncrementKey();

        $data = $entity->extractDirty($columns);
        $data = $this->toDatabaseSchema($data);

        if ($entity->isNew()) {
            $newData = $this->insertQuery()
                ->values([$data])
                ->execute()
                ->fetch() ?? [];

            foreach ($primaryKeys as $primaryKey) {
                if ($entity->hasValue($primaryKey)) {
                    continue;
                }

                if (array_key_exists($primaryKey, $newData)) {
                    $value = $newData[$primaryKey];
                } else if ($primaryKey === $autoIncrementKey) {
                    $value = $this->getConnection()->insertId();
                } else {
                    continue;
                }

                $value = $schema->getType($primaryKey)->parse($value);

                $entity->set($primaryKey, $value, ['temporary' => true]);
            }
        } else if ($data !== []) {
            $primaryValues = $entity->extract($primaryKeys);
            $conditions = QueryGenerator::combineConditions($primaryKeys, $primaryValues);
            $this->updateAll($data, $conditions);
        }

        if ($options['saveRelated'] && !$this->saveChildren($entity, $options)) {
            return false;
        }

        if ($options['events']) {
            $event = $this->dispatchEvent('Orm.afterSave', ['entity' => $entity, 'options' => $options]);

            if ($event->isStopped()) {
                return (bool) $event->getResult();
            }
        }

        return true;
    }

    /**
     * Determine whether entities already exist, and mark them not new.
     *
     * @param array $entities The entities.
     */
    protected function checkExists(array $entities): void
    {
        $primaryKeys = $this->getPrimaryKey();

        $entities = array_values($entities);

        $entities = array_filter(
            $entities,
            fn(Entity $entity): bool => $entity->isNew() && $entity->extractDirty($primaryKeys) !== []
        );

        if ($entities === []) {
            return;
        }

        $values = array_map(
            fn(Entity $entity): array => $entity->extract($primaryKeys),
            $entities
        );

        if ($values === []) {
            return;
        }

        $primaryKeys = array_map(
            fn(string $primaryKey): string => $this->aliasField($primaryKey),
            $primaryKeys
        );

        $matchedValues = $this->find([
            'fields' => $primaryKeys,
            'conditions' => QueryGenerator::normalizeConditions($primaryKeys, $values),
            'events' => false,
        ])
            ->getResult()
            ->map(fn(Entity $entity): array => $entity->extract($primaryKeys))
            ->toArray();

        if ($matchedValues === []) {
            return;
        }

        foreach ($values as $i => $data) {
            foreach ($matchedValues as $other) {
                if (array_diff_assoc($data, $other) === []) {
                    continue;
                }

                $entities[$i]->setNew(false);
                break;
            }
        }
    }

    /**
     * Create an Entity.
     *
     * @return Entity The Entity.
     */
    protected function createEntity(): Entity
    {
        $alias = $this->getClassAlias();

        $className = $this->entityLocator->find($alias);

        $entity = $this->container->build($className);

        $entity->setSource($alias);

        return $entity;
    }

    /**
     * Delete entities children.
     *
     * @param array $entities The entities.
     * @param array $options The options for deleting.
     * @return bool TRUE if the delete was successful, otherwise FALSE.
     */
    protected function deleteChildren(array $entities, array $options = []): bool
    {
        foreach ($this->relationships as $relationship) {
            if (!$relationship->isOwningSide($this)) {
                continue;
            }

            if (!$relationship->unlinkAll($entities, $options)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Inject an Entity with data.
     *
     * @param Entity $entity The Entity.
     * @param array $data The data.
     * @param array $options The Entity options.
     */
    protected function injectInto(Entity $entity, array $data, array $options): void
    {
        $options['associated'] ??= null;
        $options['accessible'] ??= null;
        $options['guard'] ??= true;
        $options['mutate'] ??= true;
        $options['parse'] ??= true;
        $options['events'] ??= true;
        $options['validate'] ??= true;
        $options['clean'] ??= false;
        $options['new'] ??= null;

        $schema = $this->getSchema();

        if ($options['parse']) {
            if ($options['events']) {
                $data = new ArrayObject($data);
                $this->dispatchEvent('Orm.beforeParse', ['data' => $data, 'options' => $options], false);
                $data = $data->getArrayCopy();
            }

            $data = $this->parseSchema($data);
        }

        $associated = null;
        if ($options['associated'] !== null) {
            $associated = static::normalizeContain($options['associated'], $this, 'associated');
            $associated = $associated['associated'];
        }

        $accessible = null;
        if ($options['accessible'] && $options['guard']) {
            $accessible = $entity->getAccessible();
            foreach ($options['accessible'] as $field => $access) {
                $entity->setAccess($field, $access);
            }
        }

        $errors = [];
        if ($options['validate']) {
            $type = $entity->isNew() ? 'create' : 'update';

            $validationData = $options['guard'] ?
                array_filter(
                    $data,
                    fn(string $field): bool => $entity->isAccessible($field),
                    ARRAY_FILTER_USE_KEY
                ) :
                $data;

            $validator = $this->getValidator();
            $errors = $validator->validate($validationData, $type);

            $entity->setErrors($errors);
        }

        $relationships = [];
        foreach ($this->relationships as $relationship) {
            $alias = $relationship->getName();
            $property = $relationship->getProperty();

            if ($associated !== null && !array_key_exists($alias, $associated)) {
                $relationships[$property] = false;

                continue;
            }

            $relationships[$property] = $alias;
        }

        foreach ($data as $field => $value) {
            if (array_key_exists($field, $errors)) {
                $entity->setInvalid($field, $value);

                continue;
            }

            $setDirty = false;

            if (is_array($value) && !$schema->hasColumn($field) && array_key_exists($field, $relationships)) {
                if (!$relationships[$field]) {
                    $value = null;
                } else {
                    $alias = $relationships[$field];
                    $relationship = $this->getRelationship($alias);

                    if ($associated === null) {
                        $relationOptions = $options;
                    } else {
                        $relationOptions = array_merge($options, $associated[$alias]);
                    }

                    $target = $relationship->getTarget();

                    if (!$relationship->hasMultiple()) {
                        $relation = $entity->get($field) ?? $target->newEmptyEntity();
                        $target->patchEntity($relation, $value, $relationOptions);

                        if (!$relation->isNew() && $relation->isEmpty()) {
                            $relation = null;
                        } else if ($relation->isDirty()) {
                            $setDirty = true;
                        }

                        $value = $relation;
                    } else {
                        $currentRelations = $entity->get($field) ?? [];

                        $relations = [];
                        foreach ($value as $i => $val) {
                            if (!is_array($val)) {
                                continue;
                            }

                            if (
                                array_key_exists('_joinData', $val) &&
                                is_array($val['_joinData']) &&
                                $relationship instanceof ManyToMany
                            ) {
                                $val['_joinData'] = $relationship->getJunction()->newEntity($val['_joinData'], $relationOptions + ['associated' => []]);
                            }

                            if (array_key_exists($i, $currentRelations)) {
                                $relation = $currentRelations[$i];

                                $target->patchEntity($relation, $val, $relationOptions);

                            } else {
                                $relation = $target->newEntity($val, $relationOptions);
                            }

                            if (!$relation->isNew() && $relation->isEmpty()) {
                                continue;
                            }

                            if (!$setDirty && $relation->isDirty()) {
                                $setDirty = true;
                            }

                            $relations[] = $relation;
                        }

                        if (!$setDirty && count($currentRelations) !== count($relations)) {
                            $setDirty = true;
                        }

                        if ($relations !== [] || $value === []) {
                            $value = $relations;
                        }
                    }
                }
            }

            $entity->set($field, $value, [
                'mutate' => $options['mutate'],
                'guard' => $options['guard'],
            ]);

            if ($setDirty) {
                $entity->setDirty($field, true);
            }
        }

        if ($accessible !== null) {
            foreach ($accessible as $field => $access) {
                $entity->setAccess($field, $access);
            }
        }

        if ($options['new'] !== null) {
            $entity->setNew($options['new']);
        }

        if ($options['clean']) {
            $entity->clean();
        }

        if ($options['events'] && $options['parse']) {
            $this->dispatchEvent('Orm.afterParse', ['entity' => $entity, 'options' => $options], false);
        }
    }

    /**
     * Save children of an entity.
     *
     * @param Entity $entity The entity.
     * @param array $options The options for saving.
     * @return bool TRUE if the save was successful, otherwise FALSE.
     */
    protected function saveChildren(Entity $entity, array $options = []): bool
    {
        $options['clean'] = false;

        foreach ($this->relationships as $relationship) {
            if (!$relationship->isOwningSide($this)) {
                continue;
            }

            if (!$relationship->saveRelated($entity, $options)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Save parents of an entity.
     *
     * @param Entity $entity The entity.
     * @param array $options The entity for saving.
     * @return bool TRUE if the save was successful, otherwise FALSE.
     */
    protected function saveParents(Entity $entity, array $options = []): bool
    {
        $options['clean'] = false;

        foreach ($this->relationships as $relationship) {
            if ($relationship->isOwningSide($this)) {
                continue;
            }

            if (!$relationship->saveRelated($entity, $options)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check whether all entities are instances of Entity.
     *
     * @param array $entities The entities.
     *
     * @throws OrmException if an entity is not an instance of Entity.
     */
    protected static function checkEntities(array $entities): void
    {
        foreach ($entities as $entity) {
            if (!$entity instanceof Entity) {
                throw OrmException::forInvalidEntity();
            }
        }
    }

    /**
     * Recursively clean entities.
     *
     * @param array $entities The entities.
     * @param Model $model The Model.
     */
    protected static function cleanEntities(array $entities, Model $model): void
    {
        $source = $model->getAlias();
        $relationships = $model->getRelationships();

        foreach ($relationships as $relationship) {
            $property = $relationship->getProperty();

            $allRelations = [];
            foreach ($entities as $entity) {
                $relation = $entity->get($property);

                if (!$relation) {
                    continue;
                }

                if ($relationship->hasMultiple()) {
                    $allRelations = array_merge($allRelations, $relation);
                } else {
                    $allRelations[] = $relation;
                }
            }

            if ($allRelations === []) {
                continue;
            }

            $target = $relationship->getTarget();

            static::cleanEntities($allRelations, $target);
        }

        foreach ($entities as $entity) {
            $entity
                ->clean()
                ->setNew(false)
                ->setSource($source);
        }
    }

    /**
     * Reset entities children.
     *
     * @param array $entities The entities.
     * @param Model $model The Model.
     */
    protected static function resetChildren(array $entities, Model $model): void
    {
        $relationships = $model->getRelationships();

        foreach ($relationships as $relationship) {
            if (!$relationship->isOwningSide()) {
                continue;
            }

            $target = $relationship->getTarget();
            $property = $relationship->getProperty();

            $allChildren = [];
            foreach ($entities as $entity) {
                $children = $entity->get($property);

                if (!$children) {
                    continue;
                }

                if ($relationship->hasMultiple()) {
                    $allChildren = array_merge($allChildren, $children);
                } else {
                    $allChildren[] = $children;
                }
            }

            if ($allChildren !== []) {
                static::resetChildren($allChildren, $target);
            }

            foreach ($allChildren as $child) {
                $child->clearTemporaryFields();

                if ($relationship instanceof ManyToMany && $child->hasValue('_joinData')) {
                    $child->get('_joinData')->clearTemporaryFields();
                }
            }
        }
    }

    /**
     * Reset entities parents.
     *
     * @param array $entities The entities.
     * @param Model $model The Model.
     */
    protected static function resetParents(array $entities, Model $model): void
    {
        $relationships = $model->getRelationships();

        foreach ($relationships as $relationship) {
            if ($relationship->isOwningSide()) {
                continue;
            }

            $target = $relationship->getTarget();
            $property = $relationship->getProperty();

            $allParents = [];
            foreach ($entities as $entity) {
                $parent = $entity->get($property);

                if (!$parent) {
                    continue;
                }

                $allParents[] = $parent;
            }

            if ($allParents !== []) {
                static::resetParents($allParents, $target);
            }

            foreach ($allParents as $parent) {
                $parent->clearTemporaryFields();
            }
        }
    }
}
