<?php
declare(strict_types=1);

namespace Fyre\ORM\Traits;

use
    Fyre\Entity\Entity,
    Fyre\ORM\Exceptions\ORMException,
    Fyre\ORM\Model;

use function
    array_key_exists,
    array_merge,
    array_reduce,
    array_reverse,
    explode,
    in_array,
    is_numeric,
    is_string,
    lcfirst,
    preg_replace,
    strtolower;

/**
 * ModelHelperTrait
 */
trait ModelHelperTrait
{

    /**
     * Check whether all entities are instances of Entity.
     * @param array $entities The entities.
     * @throws ORMException if an entity is not an instance of Entity.
     */
    public static function checkEntities(array $entities): void
    {
        foreach ($entities AS $entity) {
            if (!$entity instanceof Entity) {
                throw ORMException::forInvalidEntity();
            }
        }
    }

    /**
     * Recursively clean entities.
     * @param array $entities The entities.
     * @param Model $model The Model.
     */
    public static function cleanEntities(array $entities, Model $model): void
    {
        $source = $model->getAlias();

        foreach ($entities AS $entity) {
            $relationships = $model->getRelationships();

            foreach ($relationships AS $relationship) {
                $property = $relationship->getProperty();

                $relations = $entity->get($property);

                if (!$relations) {
                    continue;
                }

                if ($relationship->canBeJoined()) {
                    $relations = [$relations];
                }

                $target = $relationship->getTarget();

                static::cleanEntities($relations, $target);
            }

            $entity
                ->clean()
                ->setNew(false)
                ->setSource($source);
        }
    }

    /**
     * Recursively merge contain data.
     * @param array $contain The original contain.
     * @param array $newContain The new contain.
     * @return array The merged contain data.
     */
    public static function mergeContain(array $contain, array $newContain): array
    {
        foreach ($newContain AS $name => $data) {
            if (!array_key_exists($name, $contain)) {
                $contain[$name] = $data;
                continue;
            }

            foreach ($data AS $key => $value) {
                if ($key === 'contain') {
                    $contain[$name][$key] = static::mergeContain($contain[$name][$key], $value);
                } else {
                    $contain[$name][$key] = $value;
                }
            }
        }

        return $contain;
    }

    /**
     * Normalize contain data.
     * @param string|array $contain The contain data.
     * @param Model $model The Model.
     * @param bool|null $canBeJoined Whether the relationship can be joined.
     * @param bool $allowOptions Whether to allow query options.
     * @return array The normalized contain data.
     * @throws ORMException if relationship is invalid.
     */
    public static function normalizeContain(string|array $contain, Model $model, bool|null $canBeJoined = null, bool $allowOptions = true): array
    {
        $normalized = [
            'contain' => []
        ];

        if ($contain === '' || $contain === []) {
            return $normalized;
        }

        if (is_string($contain)) {
            $contain = explode('.', $contain);

            $contain = array_reduce(
                array_reverse($contain),
                fn($acc, $value) => $value ?
                    [$value => $acc] :
                    $acc,
                []
            );
        }

        foreach ($contain AS $key => $value) {
            if (is_numeric($key) || $key === 'contain') {
                $newContain = static::normalizeContain($value, $model, true);
                $normalized = static::mergeContain($normalized, $newContain);
                continue;
            }

            if ($allowOptions && (
                ($canBeJoined === false && array_key_exists($key, static::QUERY_METHODS)) ||
                ($canBeJoined === true && in_array($key, ['fields', 'autoFields']))
            )) {
                $normalized[$key] = $value;
                continue;
            }

            $relationship = $model->getRelationship($key);

            if (!$relationship) {
                throw ORMException::forInvalidRelationship($key);
            }

            $normalized['contain'][$key] ??= [];
            $newContain = static::normalizeContain($value, $relationship->getTarget(), $relationship->canBeJoined());
            $normalized['contain'][$key] = static::mergeContain($normalized['contain'][$key], $newContain);
        }

        return $normalized;
    }

    /**
     * Convert a class alias to table/field.
     * @param string $string The input string.
     * @return string The tableized string.
     */
    public static function tableize(string $string): string
    {
        $string = lcfirst($string);
        $string = preg_replace('/[A-Z]/', '_\0', $string);

        return strtolower($string);
    }

    /**
     * Unset columns for entities.
     * @param array $entities The entities.
     * @param array $columns The columns.
     */
    public static function unsetColumns(array $entities, array $columns): void
    {
        foreach ($entities AS $entity) {
            foreach ($columns AS $column) {
                $entity->unset($column);
            }
        }
    }

    /**
     * Reset entities children.
     * @param array $entities The entities.
     * @param Model $model The Model.
     */
    protected static function resetChildren(array $entities, Model $model): void
    {
        $relationships = $model->getRelationships();

        foreach ($relationships AS $relationship) {
            if (!$relationship->isOwningSide()) {
                continue;
            }

            $target = $relationship->getTarget();
            $property = $relationship->getProperty();
            $autoIncrementKey = $target->getAutoIncrementKey();

            $allChildren = [];
            $newChildren = [];
            foreach ($entities AS $entity) {
                $children = $entity->get($property);

                if (!$children) {
                    continue;
                }

                if ($relationship->canBeJoined()) {
                    $children = [$children];
                }

                $allChildren = array_merge($allChildren, $children);

                if ($entity->isNew()) {
                    $newChildren = array_merge($newChildren, $children);
                }
            }

            if ($newChildren !== []) {
                $unsetKeys = [];
                $unsetKeys[] = $relationship->getForeignKey();

                if ($autoIncrementKey && !in_array($autoIncrementKey, $unsetKeys)) {
                    $unsetKeys[] = $autoIncrementKey;
                }

                static::unsetColumns($newChildren, $unsetKeys);
            }

            if ($allChildren !== []) {
                static::resetChildren($allChildren, $target);
            }
        }
    }

    /**
     * Reset entities parents.
     * @param array $entities The entities.
     * @param Model $model The Model.
     */
    protected static function resetParents(array $entities, Model $model): void
    {
        $relationships = $model->getRelationships();

        foreach ($relationships AS $relationship) {
            if ($relationship->isOwningSide()) {
                continue;
            }

            $target = $relationship->getTarget();
            $property = $relationship->getProperty();
            $autoIncrementKey = $target->getAutoIncrementKey();

            $allParents = [];
            $newParents = [];
            $newParentEntities = [];
            foreach ($entities AS $entity) {
                $parent = $entity->get($property);

                if (!$parent) {
                    continue;
                }

                $allParents[] = $parent;

                if ($parent->isNew()) {
                    $newParents[] = $parent;
                    $newParentEntities[] = $entity;
                }
            }

            if ($newParentEntities !== []) {
                $foreignKey = $relationship->getForeignKey();
                static::unsetColumns($newParentEntities, [$foreignKey]);
            }


            if ($autoIncrementKey && $newParents !== []) {
                static::unsetColumns($newParents, [$autoIncrementKey]);
            }

            if ($allParents !== []) {
                static::resetParents($allParents, $target);
            }
        }
    }

}
