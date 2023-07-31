<?php
declare(strict_types=1);

namespace Fyre\ORM\Traits;

use Fyre\ORM\Behavior;
use Fyre\ORM\BehaviorRegistry;
use Fyre\ORM\Exceptions\OrmException;

use function array_key_exists;

/**
 * BehaviorTrait
 */
trait BehaviorTrait
{

    protected array $behaviors = [];

    /**
     * Add a Behavior to the Model.
     * @param string $name The behavior name.
     * @param array $options The behavior options.
     * @return Model The Model.
     */
    public function addBehavior(string $name, array $options = []): static
    {
        if ($this->hasBehavior($name)) {
            throw OrmException::forBehaviorExists($name);
        }

        $this->behaviors[$name] ??= BehaviorRegistry::load($name, $this, $options);

        return $this;
    }

    /**
     * Get a loaded Behavior.
     * @param string $name The behavior name.
     * @return Behavior|null The Behavior.
     */
    public function getBehavior(string $name): Behavior|null
    {
        return $this->behaviors[$name] ?? null;
    }

    /**
     * Determine if the Model has a Behavior.
     * @param string $name The behavior name.
     * @return bool TRUE if the Model has the Behavior, otherwise FALSE.
     */
    public function hasBehavior(string $name): bool
    {
        return array_key_exists($name, $this->behaviors);
    }

    /**
     * Remove a Behavior from the Model.
     * @param string $name The behavior name.
     * @return Model The Model.
     */
    public function removeBehavior(string $name): static
    {
        if (!$this->hasBehavior($name)) {
            throw OrmException::forMissingBehavior($name);
        }

        unset($this->behaviors[$name]);

        return $this;
    }

}
