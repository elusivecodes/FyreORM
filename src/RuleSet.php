<?php
declare(strict_types=1);

namespace Fyre\ORM;

use Closure;
use Fyre\Container\Container;
use Fyre\DB\QueryGenerator;
use Fyre\Entity\Entity;
use Fyre\Lang\Lang;

use function array_all;
use function array_any;
use function array_intersect;
use function array_map;
use function implode;

/**
 * RuleSet
 */
class RuleSet
{
    protected array $rules = [];

    /**
     * New RuleSet constructor.
     *
     * @param Container $container The Container.
     * @param Lang $lang The Lang.
     * @param Model $model The Model.
     */
    public function __construct(
        protected Container $container,
        protected Lang $lang,
        protected Model $model
    ) {
        $this->lang->addPath(__DIR__.'/../lang');
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
        $options['allowNullableNulls'] ??= null;

        return function(Entity $entity) use ($fields, $name, $options): bool {
            if ($fields === []) {
                return true;
            }

            if (!array_any($fields, fn(string $field): bool => $entity->isDirty($field))) {
                return true;
            }

            $values = $entity->extract($fields);

            if ($options['allowNullableNulls'] ?? array_all($values, fn(mixed $value): bool => $value === null)) {
                $schema = $this->model->getSchema();

                foreach ($values as $field => $value) {
                    if ($value === null && $schema->isNullable($field)) {
                        return true;
                    }
                }
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

            $options['message'] ??= $this->lang->get('RuleSet.existsIn', [
                'fields' => implode(', ', $fields),
                'alias' => $name,
            ]) ?? 'invalid';

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

            $options['message'] ??= $this->lang->get('RuleSet.isClean', [
                'fields' => implode(', ', $fields),
            ]) ?? 'invalid';

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
        $options['allowMultipleNulls'] ??= true;

        return function(Entity $entity) use ($fields, $options): bool {
            if ($fields === []) {
                return true;
            }

            $values = $entity->extract($fields);

            if ($options['allowMultipleNulls']) {
                $schema = $this->model->getSchema();

                foreach ($values as $field => $value) {
                    if ($value === null && $schema->isNullable($field)) {
                        return true;
                    }
                }
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
            $options['message'] ??= $this->lang->get('RuleSet.isUnique', [
                'fields' => implode(', ', $fields),
            ]) ?? 'invalid';

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
