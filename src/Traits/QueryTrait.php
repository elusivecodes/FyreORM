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
     * @param Entity $entity The Entity.
     * @param array $options The options for deleting.
     * @return bool TRUE if the delete was successful, otherwise FALSE.
     */
    public function delete(Entity $entity, array $options = []): bool
    {
        $options['cascade'] ??= true;

        $connection = $this->getConnection();

        $connection->begin();

        if (!$this->handleEvent('beforeDelete', $entity)) {
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

        if (!$this->handleEvent('afterDelete', $entity)) {
            $connection->rollback();
            return false;
        }

        $connection->commit();

        return true;
    }

    /**
     * Delete all rows matching conditions.
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

        $options['cascade'] ??= true;

        $connection = $this->getConnection();

        $connection->begin();

        $primaryKeys = $this->getPrimaryKey();

        foreach ($entities AS $entity) {
            if (!$this->handleEvent('beforeDelete', $entity)) {
                $connection->rollback();
                return false;
            }
        }

        $rowValues = [];
        foreach ($entities AS $entity) {
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

        foreach ($entities AS $entity) {
            if (!$this->handleEvent('afterDelete', $entity)) {
                $connection->rollback();
                return false;
            }
        }

        $connection->commit();

        return true;
    }

    /**
     * Determine if matching rows exist.
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
     * @param array $data The find data.
     * @return SelectQuery The Query.
     * @throws OrmException if find property does not exist.
     */
    public function find(array $data = []): SelectQuery
    {
        $query = $this->selectQuery([
            'type' => $data['type'] ?? static::READ
        ]);

        unset($data['type']);
        unset($data['strategy']);

        foreach ($data AS $property => $method) {
            if (!array_key_exists($property, static::QUERY_METHODS)) {
                throw OrmException::forInvalidFindProperty($property);
            }

            $method = static::QUERY_METHODS[$property];

            call_user_func([$query, $method], $data[$property]);
        }

        return $query;
    }

    /**
     * Retrieve a single entity.
     * @param int|string|array $primaryValues The primary key values.
     * @param array $data The find data.
     * @return Entity|null The Entity.
     */
    public function get(int|string|array $primaryValues, array $data = []): Entity|null
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
        $options['clean'] ??= true;

        $autoIncrementKey = $this->getAutoIncrementKey();

        if ($autoIncrementKey && $entity->hasValue($autoIncrementKey)) {
            $autoIncrementKey = null;
        }

        if ($options['checkExists']) {
            $this->checkExists([$entity]);
        }

        $connection = $this->getConnection();

        $connection->begin();

        if (!$this->_save($entity, $options)) {
            $connection->rollback();

            static::resetParents([$entity], $this);
            static::resetChildren([$entity], $this);

            if ($entity->isNew() && $autoIncrementKey) {
                static::resetColumns([$entity], [$autoIncrementKey]);
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

        foreach ($entities AS $entity) {
            if ($entity->hasErrors()) {
                return false;
            }
        }

        $options['checkExists'] ??= true;
        $options['checkRules'] ??= true;
        $options['saveRelated'] ??= true;
        $options['clean'] ??= true;

        if ($options['checkExists']) {
            $this->checkExists($entities);
        }

        $connection = $this->getConnection();

        $connection->begin();

        $autoIncrementKey = $this->getAutoIncrementKey();
        $autoIncrementEntities = [];

        $result = true;
        foreach ($entities AS $entity) {
            if ($autoIncrementKey && $entity->isNew() && !$entity->hasValue($autoIncrementKey)) {
                $autoIncrementEntities[] = $entity;
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

            if ($autoIncrementKey && $autoIncrementEntities !== []) {
                static::resetColumns($autoIncrementEntities, [$autoIncrementKey]);
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
     * @param Entity $entity The Entity.
     * @param array $options The options for saving.
     * @return bool TRUE if the save was successful, otherwise FALSE.
     */
    protected function _save(Entity $entity, array $options): bool
    {
        if ($options['checkRules']) {
            if (!$this->handleEvent('beforeRules', $entity)) {
                return false;
            }

            if (!$this->getRules()->validate($entity)) {
                return false;
            }

            if (!$this->handleEvent('afterRules', $entity)) {
                return false;
            }
        }

        if (!$this->handleEvent('beforeSave', $entity)) {
            return false;
        }

        if ($options['saveRelated'] && !$this->saveParents($entity, $options)) {
            return false;
        }

        $columns = $this->getSchema()->columnNames();

        $data = $entity->extractDirty($columns);
        $data = $this->toDatabaseSchema($data);

        if ($entity->isNew()) {
            $result = $this->insertQuery()
                ->values([$data])
                ->execute();

            if (!$result) {
                return false;
            }

            $autoIncrementKey = $this->getAutoIncrementKey();

            if ($autoIncrementKey && !$entity->hasValue($autoIncrementKey)) {
                $id = $this->getConnection()->insertId();

                $entity->set($autoIncrementKey, null);
                $entity->set($autoIncrementKey, $id);
            }
        } else if ($data !== []) {
            $primaryKeys = $this->getPrimaryKey();
            $primaryValues = $entity->extract($primaryKeys);
            $conditions = QueryGenerator::combineConditions($primaryKeys, $primaryValues);
            $this->updateAll($data, $conditions);
        }

        if ($options['saveRelated'] && !$this->saveChildren($entity, $options)) {
            return false;
        }

        if (!$this->handleEvent('afterSave', $entity)) {
            return false;
        }

        return true;
    }

}
