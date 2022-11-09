<?php
declare(strict_types=1);

namespace Fyre\ORM\Traits;

use
    ArrayObject,
    Fyre\DB\QueryGenerator,
    Fyre\Entity\Entity,
    Fyre\Entity\EntityLocator;

use function
    array_diff_assoc,
    array_filter,
    array_key_exists,
    array_map,
    array_values,
    count,
    is_array;

/**
 * EntityTrait
 */
trait EntityTrait
{

    protected string $entityClass;

    /**
     * Get the entity class.
     * @return string The entity class.
     */
    public function getEntityClass(): string
    {
        return $this->entityClass ??= EntityLocator::find($this->getAlias());
    }

    /**
     * Load contained data into entity.
     * @param Entity $entity The entity.
     * @param array $contain The relationships to contain.
     * @return Entity The entity.
     */
    public function loadInto(Entity $entity, array $contain): Entity
    {
        $primaryKeys = $this->getPrimaryKey();
        $primaryValues = $entity->extract($primaryKeys);

        $tempEntity = $this->get($primaryValues, [
            'contain' => $contain,
            'autoFields' => false
        ]);

        if (!$tempEntity) {
            return $entity;
        }

        foreach ($this->relationships AS $relationship) {
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
     * Build a new empty Entity.
     * @return Entity The Entity.
     */
    public function newEmptyEntity(): Entity
    {
        return $this->createEntity();
    }

    /**
     * Build a new Entity using data.
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
     * Build multiple new entities using data.
     * @param array $data The data.
     * @param array $options The Entity options.
     * @return array The entities.
     */
    public function newEntities(array $data, array $options = []): array
    {
        return array_map(
            fn(array $values): Entity => $this->newEntity($values, $data),
            $data
        );
    }

    /**
     * Update an Entity using data.
     * @param Entity $entity The Entity.
     * @param array $data The data.
     * @param array $options The Entity options.
     */
    public function patchEntity(Entity $entity, array $data, array $options = []): void
    {
        $this->injectInto($entity, $data, $options);
    }

    /**
     * Update multiple entities using data.
     * @param array $entities The entities.
     * @param array $data The data.
     * @param array $options The Entity options.
     */
    public function patchEntities(array $entities, array $data, array $options = []): void
    {
        foreach ($data AS $i => $values) {
            if (!array_key_exists($i, $entities)) {
                continue;
            }

            $this->patchEntity($entities[$i], $values, $options);
        }
    }

    /**
     * Check if entities already exist, and mark them not new.
     * @param array $entities The entities.
     */
    protected function checkExists(array $entities): void
    {
        $primaryKeys = $this->getPrimaryKey();

        $entities = array_values($entities);

        $entities = array_filter(
            $entities,
            fn(Entity $entity): bool => $entity->isNew() || $entity->extractDirty($primaryKeys) !== []
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

        $matches = $this->find([
            'fields' => $primaryKeys,
            'conditions' => QueryGenerator::normalizeConditions($primaryKeys, $values)
        ])
        ->all();

        if ($matches === []) {
            return;
        }

        $matchedValues =  array_map(
            fn(Entity $entity): array => $entity->extract($primaryKeys),
            $matches
        );

        foreach ($values AS $i => $data) {
            foreach ($matchedValues AS $other) {
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
     * @return Entity The Entity.
     */
    protected function createEntity(): Entity
    {
        $className = $this->getEntityClass();

        $entity = new $className();

        $alias = $this->getAlias();
        $entity->setSource($alias);

        return $entity;
    }

    /**
     * Inject an Entity with data.
     * @param Entity $entity The Entity.
     * @param array $options The Entity options.
     * @param array $data The data.
     */
    protected function injectInto(Entity $entity, array $data, array $options): void
    {
        $options['associated'] ??= null;
        $options['parse'] ??= true;
        $options['validate'] ??= true;
        $options['clean'] ??= false;
        $options['new'] ??= null;

        $schema = $this->getSchema();

        if ($options['parse']) {
            $data = new ArrayObject($data);
            $this->handleEvent('beforeParse', $data);
            $data = $data->getArrayCopy();

            $data = $this->parseSchema($data);
        }

        $errors = [];
        if ($options['validate']) {
            $validator = $this->getValidator();

            $type = $entity->isNew() ? 'create' : 'update';
            $errors = $validator->validate($data, $type);

            $entity->setErrors($errors);
        }

        $associated = null;
        if ($options['associated'] !== null) {
            $associated = static::normalizeContain($options['associated'], $this);
            $associated = $associated['contain'];
        }

        $relationships = [];
        foreach ($this->relationships AS $relationship) {
            $alias = $relationship->getName();
            $property = $relationship->getProperty();

            if ($associated !== null && !array_key_exists($alias, $associated)) {
                $relationships[$property] = false;
                continue;
            }

            $relationships[$property] = $alias;
        }

        foreach ($data AS $field => $value) {
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

                    if ($associated !== null) {
                        $options['associated'] = $associated[$alias]['contain'];
                    }

                    $target = $relationship->getTarget();

                    if (!$relationship->hasMultiple()) {
                        $relation = $entity->get($field) ?? $target->newEmptyEntity();
                        $target->patchEntity($relation, $value, $options);

                        if (!$relation->isNew() && $relation->isEmpty()) {
                            $relation = null;
                        } else if ($relation->isDirty()) {
                            $setDirty = true;
                        }

                        $value = $relation;
                    } else {
                        $currentRelations = $entity->get($field) ?? [];

                        $relations = [];
                        foreach ($value AS $i => $val) {
                            if (!is_array($val)) {
                                continue;
                            }

                            $relation = $currentRelations[$i] ?? $target->newEmptyEntity();
                            $target->patchEntity($relation, $val, $options);

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

            $entity->set($field, $value);

            if ($setDirty) {
                $entity->setDirty($field, true);
            }
        }

        if ($options['new'] !== null) {
            $entity->setNew($options['new']);
        }

        if ($options['clean']) {
            $entity->clean();
        }

        if ($options['parse']) {
            $this->handleEvent('afterParse', $entity);
        }
    }

}
