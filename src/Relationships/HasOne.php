<?php
declare(strict_types=1);

namespace Fyre\ORM\Relationships;

use Fyre\Entity\Entity;
use Fyre\ORM\Relationship;
use Fyre\Utility\Traits\MacroTrait;

/**
 * HasOne
 */
class HasOne extends Relationship
{
    use MacroTrait;

    protected string $strategy = 'join';

    protected array $validStrategies = ['join', 'select'];

    /**
     * Call a method on the target model.
     *
     * @param string $method The method name.
     * @param array $arguments The method arguments.
     * @return mixed The result.
     */
    public function __call(string $method, array $arguments): mixed
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $arguments);
        }

        return parent::__call($method, $arguments);
    }

    /**
     * Determine whether the relationship has multiple related items.
     *
     * @return bool TRUE if the relationship has multiple related items, otherwise FALSE.
     */
    public function hasMultiple(): bool
    {
        return false;
    }

    /**
     * Save related data for an entity.
     *
     * @param Entity $entity The entity.
     * @param array $options The options for saving.
     * @return bool TRUE if the save was successful, otherwise FALSE.
     */
    public function saveRelated(Entity $entity, array $options = []): bool
    {
        $property = $this->getProperty();
        $child = $entity->get($property);

        if (!$child || !$child instanceof Entity) {
            return true;
        }

        $bindingKey = $this->getBindingKey();
        $foreignKey = $this->getForeignKey();

        $bindingValue = $entity->get($bindingKey);

        if ($child->get($foreignKey) !== $bindingValue) {
            $child->set($foreignKey, $bindingValue, ['temporary' => true]);
        }

        if (!$this->getTarget()->save($child, $options)) {
            return false;
        }

        return true;
    }
}
