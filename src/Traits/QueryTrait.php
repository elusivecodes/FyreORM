<?php
declare(strict_types=1);

namespace Fyre\ORM\Traits;

use Fyre\DB\QueryGenerator;
use Fyre\Entity\Entity;
use Fyre\ORM\Exceptions\OrmException;
use Fyre\ORM\Queries\SelectQuery;

use function array_filter;
use function array_key_exists;
use function array_map;
use function call_user_func;
use function count;

/**
 * QueryTrait
 */
trait QueryTrait
{
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

        if ($options['events'] && !$this->handleEvent('beforeDelete', $entity, $options)) {
            $connection->rollback();

            return false;
        }

        $primaryKeys = $this->getPrimaryKey();
        $primaryValues = $entity->extract($primaryKeys);
        $conditions = QueryGenerator::combineConditions($primaryKeys, $primaryValues);

        if (!$this->deleteAll($conditions)) {
            $connection->rollback();

            return false;
        }

        if ($options['cascade'] && !$this->deleteChildren([$entity], $options)) {
            $connection->rollback();

            return false;
        }

        if ($options['events'] && !$this->handleEvent('afterDelete', $entity, $options)) {
            $connection->rollback();

            return false;
        }

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
     * @param array $entities The entities.
     * @param array $options The options for deleting.
     * @return bool TRUE if the delete was successful, otherwise FALSE.
     */
    public function deleteMany(array $entities, array $options = []): bool
    {
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

        $primaryKeys = $this->getPrimaryKey();

        if ($options['events']) {
            foreach ($entities as $entity) {
                if (!$this->handleEvent('beforeDelete', $entity, $options)) {
                    $connection->rollback();

                    return false;
                }
            }
        }

        $rowValues = [];
        foreach ($entities as $entity) {
            $rowValues[] = $entity->extract($primaryKeys);
        }

        $conditions = QueryGenerator::normalizeConditions($primaryKeys, $rowValues);

        if (!$this->deleteAll($conditions)) {
            $connection->rollback();

            return false;
        }

        if ($options['cascade'] && !$this->deleteChildren($entities, $options)) {
            $connection->rollback();

            return false;
        }

        if ($options['events']) {
            foreach ($entities as $entity) {
                if (!$this->handleEvent('afterDelete', $entity, $options)) {
                    $connection->rollback();

                    return false;
                }
            }
        }

        $connection->commit();

        return true;
    }

    /**
     * Determine if matching rows exist.
     *
     * @param array $conditions The conditions.
     * @return bool TRUE if matching rows exist, otherwise FALSE.
     */
    public function exists(array $conditions): bool
    {
        return $this->find()
            ->enableAutoFields(false)
            ->where($conditions)
            ->limit(1)
            ->count() > 0;
    }

    /**
     * Create a new SelectQuery.
     *
     * @param array $data The find data.
     * @return SelectQuery The Query.
     *
     * @throws OrmException if find property does not exist.
     */
    public function find(array $data = []): SelectQuery
    {
        $methods = [];
        $options = [];

        foreach ($data as $property => $value) {
            if (array_key_exists($property, static::QUERY_METHODS)) {
                $method = static::QUERY_METHODS[$property];
                $methods[$method] = $value;
            } else {
                $options[$property] = $value;
            }
        }

        $options['alias'] ??= null;
        $options['connectionType'] ??= static::READ;

        $query = $this->selectQuery($options);

        foreach ($methods as $method => $value) {
            call_user_func([$query, $method], $value);
        }

        return $query;
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

            if ($entity->isNew()) {
                $primaryKeys = $this->getPrimaryKey();
                static::resetColumns([$entity], $primaryKeys);
            }

            return false;
        }

        $connection->commit();

        if ($options['clean']) {
            static::cleanEntities([$entity], $this);
        }

        return true;
    }

    /**
     * Save multiple entities.
     *
     * @param array $entities The entities.
     * @param array $options The options for saving.
     * @return bool TRUE if the save was successful, otherwise FALSE.
     */
    public function saveMany(array $entities, array $options = []): bool
    {
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

        $newEntities = [];

        $result = true;
        foreach ($entities as $entity) {
            if ($entity->isNew()) {
                $newEntities[] = $entity;
            }

            if (!$this->_save($entity, $options)) {
                $result = false;
                break;
            }
        }

        if (!$result) {
            $connection->rollback();

            static::resetParents($entities, $this);
            static::resetChildren($entities, $this);

            if ($newEntities !== []) {
                $primaryKeys = $this->getPrimaryKey();
                static::resetColumns($newEntities, $primaryKeys);
            }

            return false;
        }

        $connection->commit();

        if ($options['clean']) {
            static::cleanEntities($entities, $this);
        }

        return true;
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
     * Save a single Entity.
     *
     * @param Entity $entity The Entity.
     * @param array $options The options for saving.
     * @return bool TRUE if the save was successful, otherwise FALSE.
     */
    protected function _save(Entity $entity, array $options): bool
    {
        if ($options['checkRules']) {
            if ($options['events'] && !$this->handleEvent('beforeRules', $entity, $options)) {
                return false;
            }

            if (!$this->getRules()->validate($entity)) {
                return false;
            }

            if ($options['events'] && !$this->handleEvent('afterRules', $entity, $options)) {
                return false;
            }
        }

        if ($options['events'] && !$this->handleEvent('beforeSave', $entity, $options)) {
            return false;
        }

        if ($options['saveRelated'] && !$this->saveParents($entity, $options)) {
            return false;
        }

        $schema = $this->getSchema();
        $columns = $schema->columnNames();
        $primaryKeys = $this->getPrimaryKey();

        $data = $entity->extractDirty($columns);
        $data = $this->toDatabaseSchema($data);

        if ($entity->isNew()) {
            $result = $this->insertQuery()
                ->values([$data])
                ->execute();

            $newData = $result->fetch() ?? [];

            $autoIncrementKey = $this->getAutoIncrementKey();

            foreach ($primaryKeys as $primaryKey) {
                if ($entity->hasValue($primaryKey)) {
                    continue;
                }

                if ($primaryKey === $autoIncrementKey) {
                    $value = $this->getConnection()->insertId();
                } else if (array_key_exists($primaryKey, $newData)) {
                    $value = $newData[$primaryKey];
                } else {
                    continue;
                }

                $value = $schema->getType($primaryKey)->parse($value);

                $entity->set($primaryKey, null);
                $entity->set($primaryKey, $value);
            }
        } else if ($data !== []) {
            $primaryValues = $entity->extract($primaryKeys);
            $conditions = QueryGenerator::combineConditions($primaryKeys, $primaryValues);
            $this->updateAll($data, $conditions);
        }

        if ($options['saveRelated'] && !$this->saveChildren($entity, $options)) {
            return false;
        }

        if ($options['events'] && !$this->handleEvent('afterSave', $entity, $options)) {
            return false;
        }

        return true;
    }
}
