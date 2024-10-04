<?php
declare(strict_types=1);

namespace Fyre\ORM\Traits;

use Fyre\Entity\Entity;
use Fyre\ORM\Exceptions\OrmException;
use Fyre\ORM\Model;
use Fyre\ORM\Queries\SelectQuery;

use function array_key_exists;
use function array_merge;
use function array_reduce;
use function array_reverse;
use function array_unique;
use function explode;
use function in_array;
use function is_numeric;
use function is_string;
use function preg_replace;
use function strtolower;

/**
 * HelperTrait
 */
trait HelperTrait
{
    /**
     * Check whether all entities are instances of Entity.
     *
     * @param array $entities The entities.
     *
     * @throws OrmException if an entity is not an instance of Entity.
     */
    public static function checkEntities(array $entities): void
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
    public static function cleanEntities(array $entities, Model $model): void
    {
        $source = $model->getAlias();

        foreach ($entities as $entity) {
            $relationships = $model->getRelationships();

            foreach ($relationships as $relationship) {
                $property = $relationship->getProperty();

                $relations = $entity->get($property);

                if (!$relations) {
                    continue;
                }

                if (!$relationship->hasMultiple()) {
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
     * @param int $depth The contain depth.
     * @return array The normalized contain data.
     *
     * @throws OrmException if a relationship is not valid.
     */
    public static function normalizeContain(array|string $contain, Model $model, int $depth = 0): array
    {
        $normalized = [
            'contain' => [],
        ];

        if ($contain === '' || $contain === []) {
            return $normalized;
        }

        if (is_string($contain)) {
            $contain = explode('.', $contain);

            $contain = array_reduce(
                array_reverse($contain),
                fn(array $acc, string $value): array => $value ?
                    [$value => $acc] :
                    $acc,
                []
            );
        }

        foreach ($contain as $key => $value) {
            if (is_numeric($key) || $key === 'contain') {
                $newContain = static::normalizeContain($value, $model, $depth);
                $normalized = static::mergeContain($normalized, $newContain);

                continue;
            }

            if (
                $depth > 0 &&
                (
                    array_key_exists($key, SelectQuery::QUERY_METHODS) ||
                    in_array($key, ['autoFields', 'strategy', 'type'])
                )
            ) {
                $normalized[$key] = $value;

                continue;
            }

            $relationship = $model->getRelationship($key);

            if (!$relationship) {
                throw OrmException::forInvalidRelationship($key);
            }

            $normalized['contain'][$key] ??= [];
            $newContain = static::normalizeContain($value, $relationship->getTarget(), $depth + 1);
            $normalized['contain'][$key] = static::mergeContain($normalized['contain'][$key], $newContain);
        }

        return $normalized;
    }

    /**
     * Reset columns for entities.
     *
     * @param array $entities The entities.
     * @param array $columns The columns.
     */
    public static function resetColumns(array $entities, array $columns): void
    {
        foreach ($entities as $entity) {
            foreach ($columns as $column) {
                $value = $entity->get($column);
                $original = $entity->getOriginal($column);

                $entity->unset($column);

                if ($original !== null && $value !== $original) {
                    $entity->set($column, $original);
                }
            }
        }
    }

    /**
     * Convert a class alias to table/field.
     *
     * @param string $string The input string.
     * @return string The tableized string.
     */
    public static function tableize(string $string): string
    {
        $string = preg_replace('/(?<=[^A-Z])[A-Z]/', '_\0', $string);

        return strtolower($string);
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
            $newChildren = [];
            foreach ($entities as $entity) {
                $children = $entity->get($property);

                if (!$children) {
                    continue;
                }

                if (!$relationship->hasMultiple()) {
                    $children = [$children];
                }

                $allChildren = array_merge($allChildren, $children);

                if ($entity->isNew()) {
                    $newChildren = array_merge($newChildren, $children);
                }
            }

            if ($newChildren !== []) {
                $primaryKeys = $target->getPrimaryKey();
                $foreignKey = $relationship->getForeignKey();
                $resetKeys = array_unique([...$primaryKeys, $foreignKey]);

                static::resetColumns($newChildren, $resetKeys);
            }

            if ($allChildren !== []) {
                static::resetChildren($allChildren, $target);
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
            $newParents = [];
            $newParentEntities = [];
            foreach ($entities as $entity) {
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
                static::resetColumns($newParentEntities, [$foreignKey]);
            }

            if ($newParents !== []) {
                $primaryKeys = $target->getPrimaryKey();
                static::resetColumns($newParents, $primaryKeys);
            }

            if ($allParents !== []) {
                static::resetParents($allParents, $target);
            }
        }
    }
}
