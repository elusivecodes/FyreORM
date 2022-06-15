<?php
declare(strict_types=1);

namespace Fyre\ORM\Traits;

use
    Fyre\DB\QueryGenerator,
    Fyre\Entity\Entity,
    Fyre\ORM\Exceptions\OrmException,
    Fyre\ORM\Model,
    Fyre\ORM\Query;

use function
    array_chunk,
    array_key_exists,
    array_map,
    array_merge,
    array_shift,
    count,
    range;

/**
 * ModelQueryTrait
 */
trait ModelQueryTrait
{

    protected int $batchSize = 100;

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

        if ($this->beforeDelete($entity) === false) {
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

        if ($this->afterDelete($entity) === false) {
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
        $this->query()
            ->delete()
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
            if ($this->beforeDelete($entity) === false) {
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
            if ($this->afterDelete($entity) === false) {
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
     * Create a new Query.
     * @param array $data The find data.
     * @return Query The Query.
     * @throws OrmException if find property does not exist.
     */
    public function find(array $data = []): Query
    {
        $query = $this->query([
            'type' => $data['type'] ?? Model::READ
        ]);

        unset($data['type']);
        unset($data['strategy']);

        foreach ($data AS $property => $method) {
            if (!array_key_exists($property, static::QUERY_METHODS)) {
                throw OrmException::forInvalidFindProperty($property);
            }

            $method = static::QUERY_METHODS[$property];

            $query->$method($data[$property]);
        }

        return $this->beforeFind($query);
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
        $isNew = $entity->isNew();

        if (!$isNew && !$entity->isDirty()) {
            return true;
        }

        if ($entity->hasErrors()) {
            return false;
        }

        $options['checkExists'] ??= true;
        $options['checkRules'] ??= true;
        $options['saveRelated'] ??= true;
        $options['clean'] ??= true;

        $primaryKeys = $this->getPrimaryKey();

        if ($options['checkExists'] && $isNew && $entity->extractDirty($primaryKeys) !== []) {
            $primaryValues = $entity->extract($primaryKeys);
            $conditions = QueryGenerator::combineConditions($primaryKeys, $primaryValues);

            $isNew = $this->find([
                'conditions' => $conditions
            ])->count() === 0;

            $entity->setNew($isNew);
        }

        $connection = $this->getConnection();

        $connection->begin();

        if ($this->beforeRules($entity) === false) {
            $connection->rollback();
            return false;
        }

        if ($options['checkRules'] && $this->getRules()->validate($entity) === false) {
            $connection->rollback();
            return false;
        }

        if ($this->afterRules($entity) === false) {
            $connection->rollback();
            return false;
        }

        if ($this->beforeSave($entity) === false) {
            $connection->rollback();
            return false;
        }

        if ($options['saveRelated'] && !$this->saveParents([$entity], $options)) {
            $connection->rollback();
            return false;
        }

        $columns = $this->getSchema()->columnNames();

        $data = $entity->extractDirty($columns);
        $data = $this->toDatabaseSchema($data);

        if ($isNew) {
            $result = $this->query()
                ->insert($data)
                ->execute();
        } else if ($data !== []) {
            $primaryValues = $entity->extract($primaryKeys);
            $conditions = QueryGenerator::combineConditions($primaryKeys, $primaryValues);
            $result = $this->updateAll($data, $conditions);
        } else {
            $result = true;
        }

        if (!$result) {
            $connection->rollback();
            static::resetParents([$entity], $this);
            return false;
        }

        $autoIncrementKey = $this->getAutoIncrementKey();

        if ($isNew && $autoIncrementKey) {
            $id = $connection->insertId();
            $entity->set($autoIncrementKey, $id);
        }

        if ($options['saveRelated'] && !$this->saveChildren([$entity], $options)) {
            $connection->rollback();
            static::resetParents([$entity], $this);
            static::resetChildren([$entity], $this);

            if ($autoIncrementKey) {
                static::unsetColumns([$entity], [$autoIncrementKey]);
            }

            return false;
        }

        if ($this->afterSave($entity) === false) {
            $connection->rollback();
            static::resetParents([$entity], $this);
            static::resetChildren([$entity], $this);

            if ($autoIncrementKey) {
                static::unsetColumns([$entity], [$autoIncrementKey]);
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

        $primaryKeys = $this->getPrimaryKey();

        if ($options['checkExists']) {
            foreach ($entities AS $entity) {
                if (!$entity->isNew() || $entity->extractDirty($primaryKeys) === []) {
                    continue;
                }

                $primaryValues = $entity->extract($primaryKeys);
                $conditions = QueryGenerator::combineConditions($primaryKeys, $primaryValues);

                $isNew = $this->find([
                    'conditions' => $conditions
                ])->count() === 0;

                $entity->setNew($isNew);
            }
        }

        $connection = $this->getConnection();

        $connection->begin();

        foreach ($entities AS $entity) {
            if ($this->beforeRules($entity) === false) {
                $connection->rollback();
                return false;
            }
        }

        if ($options['checkRules']) {
            $rules = $this->getRules();

            if ($rules->validateMany($entities) === false) {
                $connection->rollback();
                return false;
            }
        }

        foreach ($entities AS $entity) {
            if ($this->afterRules($entity) === false) {
                $connection->rollback();
                return false;
            }
        }

        foreach ($entities AS $entity) {
            if ($this->beforeSave($entity) === false) {
                $connection->rollback();
                static::resetParents($entities, $this);
                return false;
            }
        }

        if ($options['saveRelated'] && !$this->saveParents($entities, $options)) {
            $connection->rollback();
            return false;
        }

        $columns = $this->getSchema()->columnNames();

        $insertEntities = [];
        $insertData = [];
        $updateData = [];

        foreach ($entities AS $entity) {
            $data = $entity->extractDirty($columns);
            $data = $this->toDatabaseSchema($data);

            if ($entity->isNew()) {
                $insertData[] = $data;
                $insertEntities[] = $entity;
            } else if ($data !== []) {
                $values = $entity->extract($primaryKeys);

                $updateData[] = array_merge($data, $values);
            }
        }

        $result = true;
        $insertIds = [];

        $batchSize = $options['batchSize'] ?? $this->batchSize;

        if ($insertData !== []) {
            $insertChunks = array_chunk($insertData, $batchSize);

            while ($result && $insertChunks !== []) {
                $insertChunk = array_shift($insertChunks);

                $result = $this->query()
                    ->insertBatch($insertChunk)
                    ->execute();

                $id = $connection->insertId();

                $ids = range($id, $id + count($insertChunk) - 1);
                $insertIds = array_merge($insertIds, $ids);
            }
        }

        if ($result && $updateData !== []) {
            $updateChunks = array_chunk($updateData, $batchSize);

            while ($result && $updateChunks !== []) {
                $updateChunk = array_shift($updateChunks);

                $result = $this->query()
                    ->updateBatch($updateChunk, $primaryKeys)
                    ->execute();
            }
        }

        if (!$result) {
            $connection->rollback();
            static::resetParents($entities, $this);
            return false;
        }

        $autoIncrementKey = $this->getAutoIncrementKey();

        if ($insertEntities !== [] && $autoIncrementKey) {
            foreach ($insertEntities AS $i => $entity) {
                $entity->set($autoIncrementKey, $insertIds[$i]);
            }
        }

        if ($options['saveRelated'] && !$this->saveChildren($entities, $options)) {
            $connection->rollback();
            static::resetParents($entities, $this);
            static::resetChildren($entities, $this);

            if ($autoIncrementKey) {
                static::unsetColumns($entities, [$autoIncrementKey]);
            }

            return false;
        }

        foreach ($entities AS $entity) {
            if ($this->afterSave($entity) === false) {
                $connection->rollback();

                if ($autoIncrementKey) {
                    static::unsetColumns($entities, [$autoIncrementKey]);
                }

                static::resetParents($entities, $this);
                static::resetChildren($entities, $this);

                return false;
            }
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
        $this->query()
            ->update($data)
            ->where($conditions)
            ->execute();

        return $this->getConnection()->affectedRows();
    }

}
