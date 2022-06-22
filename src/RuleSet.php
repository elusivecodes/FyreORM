<?php
declare(strict_types=1);

namespace Fyre\ORM;

use
    Closure,
    Fyre\DB\QueryGenerator,
    Fyre\Entity\Entity,
    Fyre\Lang\Lang;

use function
    array_diff_assoc,
    array_filter,
    array_intersect_assoc,
    array_keys,
    array_map,
    array_merge,
    array_slice,
    array_values,
    implode,
    in_array;

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
            $primaryKeys = $target->getPrimaryKey();

            $matches = $target->find([
                'fields' => $primaryKeys,
                'conditions' => QueryGenerator::normalizeConditions($primaryKeys, $values)
            ])
            ->all();

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
     * Create an "is unique" rule.
     * @param array $fields The fields.
     * @param array $options The options.
     * @return Closure The rule.
     */
    public function isUnique(array $fields, array $options = []): Closure
    {
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
                fn(Entity $item): array => $item->extract($fields),
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

            $matches = $this->model->find([
                'fields' => $fields,
                'conditions' => QueryGenerator::normalizeConditions($fields, $values)
            ])
            ->all();

            $matchedValues =  array_map(
                fn(Entity $item): array => $item->extract($fields),
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
     * @param Entity $item The Entity.
     * @return bool TRUE if the validation was successful, otherwise FALSE.
     */
    public function validate(Entity $item): bool
    {
        $result = true;
        foreach ($this->rules AS $rule) {
            if ($rule([$item]) === false) {
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
