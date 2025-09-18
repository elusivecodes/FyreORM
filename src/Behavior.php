<?php
declare(strict_types=1);

namespace Fyre\ORM;

use Fyre\Event\EventListenerInterface;
use Fyre\Utility\Traits\MacroTrait;

use function array_filter;
use function array_replace;
use function method_exists;

/**
 * Behavior
 */
abstract class Behavior implements EventListenerInterface
{
    use MacroTrait;

    protected static array $defaults = [];

    protected array $config;

    /**
     * New Behavior constructor.
     *
     * @param Model $model The Model.
     * @param array $options The behavior options.
     */
    public function __construct(
        protected Model $model,
        array $options = []
    ) {
        $this->config = array_replace(static::$defaults, $options);
    }

    /**
     * Get the config.
     *
     * @return array The behavior config.
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Get the Model.
     *
     * @return Model The Model.
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * Get the implemented events.
     *
     * @return array The implemented events.
     */
    public function implementedEvents(): array
    {
        return array_filter([
            'Orm.afterDelete' => 'afterDelete',
            'Orm.afterDeleteCommit' => 'afterDeleteCommit',
            'Orm.afterFind' => 'afterFind',
            'Orm.afterParse' => 'afterParse',
            'Orm.afterRules' => 'afterRules',
            'Orm.afterSave' => 'afterSave',
            'Orm.afterSaveCommit' => 'afterSaveCommit',
            'Orm.beforeDelete' => 'beforeDelete',
            'Orm.beforeFind' => 'beforeFind',
            'Orm.beforeParse' => 'beforeParse',
            'Orm.beforeRules' => 'beforeRules',
            'Orm.beforeSave' => 'beforeSave',
        ], fn(string $method): bool => method_exists($this, $method));
    }
}
