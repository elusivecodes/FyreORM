<?php
declare(strict_types=1);

namespace Fyre\ORM;

use Closure;
use Fyre\DB\QueryGenerator;
use Fyre\Entity\Entity;
use Fyre\Lang\Lang;

use function array_intersect;
use function array_map;
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
     *
     * @param Model $model The Model.
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Add a rule.
     *
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
     *
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
            'name' => $name,
        ]) ?? 'invalid';

        return function(Entity $entity) use ($fields, $name, $options): bool {
            if ($fields === []) {
                return true;
            }

            $values = $entity->extract($fields);

            if ($options['allowNullableNulls'] && in_array(null, $values, true)) {
                return true;
            }

            $relationship = $this->model->getRelationship($name);
            $target = $relationship->getTarget();

            $targetFields = array_map(
                fn(string $targetField): string => $target->aliasField($targetField),
                $options['targetFields'] ?? $target->getPrimaryKey()
            );

            $query = $target->find([
                'fields' => $targetFields,
                'conditions' => QueryGenerator::combineConditions($targetFields, $values),
                'events' => false,
            ]);

            if ($options['callback']) {
                $query = $options['callback']($query);
            }

            if ($query->count()) {
                return true;
            }

            foreach ($fields as $field) {
                $entity->setError($field, $options['message']);
            }

            return false;
        };
    }

    /**
     * Create an "is clean" rule.
     *
     * @param array $fields The fields.
     * @param array $options The options.
     * @return Closure The rule.
     */
    public function isClean(array $fields, array $options = []): Closure
    {
        $options['message'] ??= Lang::get('RuleSet.isClean', [
            'fields' => implode(', ', $fields),
        ]) ?? 'invalid';

        return function(Entity $entity) use ($fields, $options): bool {
            if ($fields === []) {
                return true;
            }

            if ($entity->isNew()) {
                return true;
            }

            $dirty = array_intersect($fields, $entity->getDirty());

            if ($dirty === []) {
                return true;
            }

            foreach ($dirty as $field) {
                $entity->setError($field, $options['message']);
            }

            return false;
        };
    }

    /**
     * Create an "is unique" rule.
     *
     * @param array $fields The fields.
     * @param array $options The options.
     * @return Closure The rule.
     */
    public function isUnique(array $fields, array $options = []): Closure
    {
        $options['callback'] ??= null;
        $options['allowMultipleNulls'] ??= false;
        $options['message'] ??= Lang::get('RuleSet.isUnique', [
            'fields' => implode(', ', $fields),
        ]) ?? 'invalid';

        return function(Entity $entity) use ($fields, $options): bool {
            if ($fields === []) {
                return true;
            }

            $values = $entity->extract($fields);

            if ($options['allowMultipleNulls'] && in_array(null, $values, true)) {
                return true;
            }

            $aliasedFields = array_map(
                fn(string $field): string => $this->model->aliasField($field),
                $fields
            );

            $conditions = QueryGenerator::combineConditions($aliasedFields, $values);

            if (!$entity->isNew()) {
                $primaryKeys = $this->model->getPrimaryKey();
                $primaryValues = $entity->extract($primaryKeys);

                $primaryKeys = array_map(
                    fn(string $primaryKey): string => $this->model->aliasField($primaryKey),
                    $primaryKeys
                );

                $conditions['not'] = QueryGenerator::combineConditions($primaryKeys, $primaryValues);
            }

            $query = $this->model->find([
                'fields' => $aliasedFields,
                'conditions' => $conditions,
                'events' => false,
            ]);

            if ($options['callback']) {
                $query = $options['callback']($query);
            }

            if (!$query->count()) {
                return true;
            }

            foreach ($fields as $field) {
                $entity->setError($field, $options['message']);
            }

            return false;
        };
    }

    /**
     * Validate an entity.
     *
     * @param Entity $entity The Entity.
     * @return bool TRUE if the validation was successful, otherwise FALSE.
     */
    public function validate(Entity $entity): bool
    {
        $result = true;
        foreach ($this->rules as $rule) {
            if ($rule($entity) === false) {
                $result = false;
            }
        }

        return $result;
    }
}
