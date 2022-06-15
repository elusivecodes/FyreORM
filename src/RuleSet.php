<?php
declare(strict_types=1);

namespace Fyre\ORM;

use
    Closure,
    Fyre\DB\QueryGenerator,
    Fyre\Entity\Entity,
    Fyre\Lang\Lang;

use function
    array_combine,
    array_diff_assoc,
    array_search,
    array_slice,
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

        return function(Entity $item) use ($fields, $name, $options): bool {
            if ($fields === []) {
                return true;
            }

            $values = $item->extract($fields);

            if ($options['allowNullableNulls'] && in_array(null, $values, true)) {
                return true;
            }

            $relationship = $this->model->getRelationship($name);
            $target = $relationship->getTarget();
            $primaryKeys = $target->getPrimaryKey();

            $conditions = QueryGenerator::combineConditions($primaryKeys, $values);

            if ($target->exists($conditions)) {
                return true;
            }

            $item->setError($fields[0], $options['message']);

            return false;
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

        return function(Entity $item, array|null $batchItems = null) use ($fields, $options): bool {
            if ($fields === []) {
                return true;
            }

            $values = $item->extract($fields);

            if ($options['allowMultipleNulls'] && in_array(null, $values, true)) {
                return true;
            }

            $batchItems ??= [$item];
            $itemIndex = array_search($item, $batchItems, true);
            $others = array_slice($batchItems, 0, $itemIndex);

            foreach ($others AS $other) {
                $otherValues = $other->extract($fields);

                if (array_diff_assoc($values, $otherValues) !== []) {
                    continue;
                }

                $item->setError($fields[0], $options['message']);

                return false;
            }

    
            $conditions = QueryGenerator::combineConditions($fields, $values);

            if ($this->model->exists($conditions)) {
                $item->setError($fields[0], $options['message']);
    
                return false;
            }

            return true;
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
            if ($rule($item) === false) {
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
    public function validateMany(array $items = []): bool
    {
        $result = true;
        foreach ($items AS $item) {
            foreach ($this->rules AS $rule) {
                if ($rule($item, $items) === false) {
                    $result = false;
                }
            }
        }

        return $result;
    }

}
