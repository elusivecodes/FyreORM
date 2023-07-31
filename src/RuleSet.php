<?php
declare(strict_types=1);

namespace Fyre\ORM;

use Closure;
use Fyre\DB\QueryGenerator;
use Fyre\Entity\Entity;
use Fyre\Lang\Lang;

use function array_diff_assoc;
use function array_filter;
use function array_intersect;
use function array_intersect_assoc;
use function array_keys;
use function array_map;
use function array_merge;
use function array_slice;
use function array_values;
use function implode;
use function in_array;

/**
 * RuleSet
 */
class RuleSet
{

    protected Model $model;

    protected array $rules = [];

    /**
     * New RuleSet constructor.
     * @param Model $model The Model.
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Add a rule.
     * @param Closure $rule The rule.
     * @return RuleSet The RuleSet.
     */
    public function add(Closure $rule): static
    {
        $this->rules[] = $rule;

        return $this;
    }

    /**
     * Create an "exists in" rule.
     * @param array $fields The fields.
     * @param string $name The relationship name.
     * @param array $options The options.
     * @return Closure The rule.
     */
    public function existsIn(array $fields, string $name, array $options = []): Closure
    {
        $options['targetFields'] ??= null;
        $options['callback'] ??= null;
        $options['allowNullableNulls'] ??= false;
        $options['message'] ??= Lang::get('RuleSet.existsIn', [
            'fields' => implode(', ', $fields),
            'name' => $name
        ]) ?? 'invalid';

        return function(array $entities) use ($fields, $name, $options): bool {
            if ($fields === []) {
                return true;
            }

            $entities = array_values($entities);

            $values = array_map(
                fn(Entity $entity): array => $entity->extract($fields),
                $entities
            );

            if ($options['allowNullableNulls']) {
                $values = array_filter(
                    $values,
                    fn(array $data): bool => !in_array(null, $data, true)
                );
            }

            if ($values === []) {
                return true;
            }

            $result = true;

            $relationship = $this->model->getRelationship($name);
            $target = $relationship->getTarget();
            $options['targetFields'] ??= $target->getPrimaryKey();

            $query = $target->find([
                'fields' => $options['targetFields'],
                'conditions' => QueryGenerator::normalizeConditions($options['targetFields'], $values)
            ]);

            if ($options['callback']) {
                $query = $options['callback']($query);
            }

            $matches = $query->all();

            $matchedValues =  array_map(
                fn(Entity $entity): array => $entity->extract($fields),
                $matches
            );

            foreach ($values AS $i => $data) {
                $matched = false;
                foreach ($matchedValues AS $other) {
                    if (array_diff_assoc($data, $other) === []) {
                        continue;
                    }

                    $matched = true;
                    break;
                }

                if ($matched) {
                    continue;
                }

                foreach ($fields AS $field) {
                    $entities[$i]->setError($field, $options['message']);
                }

                $result = false;
            }

            return $result;
        };
    }

    /**
     * Create an "is clean" rule.
     * @param array $fields The fields.
     * @param array $options The options.
     * @return Closure The rule.
     */
    public function isClean(array $fields, array $options = []): Closure
    {
        $options['message'] ??= Lang::get('RuleSet.isClean', [
            'fields' => implode(', ', $fields)
        ]) ?? 'invalid';

        return function(array $entities) use ($fields, $options): bool {
            if ($fields === []) {
                return true;
            }

            $result = true;

            foreach ($entities AS $entity) {
                if ($entity->isNew()) {
                    continue;
                }

                $dirty = array_intersect($fields, $entity->getDirty());

                if ($dirty === []) {
                    continue;
                }

                $result = false;

                foreach ($dirty AS $field) {
                    $entity->setError($field, $options['message']);
                }
            }

            return $result;
        };
    }

    /**
     * Create an "is unique" rule.
     * @param array $fields The fields.
     * @param array $options The options.
     * @return Closure The rule.
     */
    public function isUnique(array $fields, array $options = []): Closure
    {
        $options['callback'] ??= null;
        $options['allowMultipleNulls'] ??= false;
        $options['message'] ??= Lang::get('RuleSet.isUnique', [
            'fields' => implode(', ', $fields)
        ]) ?? 'invalid';

        return function(array $entities) use ($fields, $options): bool {
            if ($fields === []) {
                return true;
            }

            $entities = array_values($entities);

            $values = array_map(
                fn(Entity $entity): array => $entity->extract($fields),
                $entities
            );

            if ($options['allowMultipleNulls']) {
                $values = array_filter(
                    $values,
                    fn(array $data): bool => !in_array(null, $data, true)
                );
            }

            if ($values === []) {
                return true;
            }

            $conditions = QueryGenerator::normalizeConditions($fields, $values);

            $primaryKeys = $this->model->getPrimaryKey();

            $primaryValues = array_map(
                fn(Entity $entity): array => $entity->extract($primaryKeys),
                array_filter(
                    $entities,
                    fn(Entity $entity): bool => !$entity->isNew()
                )
            );

            if ($primaryValues !== []) {
                $conditions['not'] = QueryGenerator::normalizeConditions($primaryKeys, $primaryValues);
            }

            $query = $this->model->find([
                'fields' => $fields,
                'conditions' => $conditions
            ]);

            if ($options['callback']) {
                $query = $options['callback']($query);
            }

            $matches = $query->all();

            $matchedValues =  array_map(
                fn(Entity $entity): array => $entity->extract($fields),
                $matches
            );

            $result = true;

            foreach ($values AS $i => $data) {
                $others = array_slice($values, 0, $i);
                $others = array_merge($others, $matchedValues);

                foreach ($others AS $other) {
                    if (array_diff_assoc($data, $other) !== []) {
                        continue;
                    }

                    $intersect = array_intersect_assoc($data, $other);
                    $errorFields = array_keys($intersect);

                    foreach ($errorFields AS $field) {
                        $entities[$i]->setError($field, $options['message']);
                    }

                    $result = false;
                    break;
                }
            }

            return $result;
        };
    }

    /**
     * Validate an entity.
     * @param Entity $entity The Entity.
     * @return bool TRUE if the validation was successful, otherwise FALSE.
     */
    public function validate(Entity $entity): bool
    {
        $result = true;
        foreach ($this->rules AS $rule) {
            if ($rule([$entity]) === false) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * Validate multiple entities.
     * @param array $entities The entities.
     * @return bool TRUE if the validation was successful, otherwise FALSE.
     */
    public function validateMany(array $entities = []): bool
    {
        $result = true;
        foreach ($this->rules AS $rule) {
            if ($rule($entities) === false) {
                $result = false;
            }
        }

        return $result;
    }

}
